<?php

namespace Tests\Feature\Developer;

use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\WebhookSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        // Extract client_id from URL for PUT/GET/DELETE on existing clients
        if (preg_match('#/admin/clients/([^/?]+)#', $request->url(), $m)) {
            $clientId = $m[1];
        } else {
            $clientId = 'test-client-' . uniqid();
        }
        return Http::response([
            'client_id' => $clientId,
            'client_secret' => 'test-raw-secret-' . uniqid(),
            'client_name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'scope' => 'openid',
            'token_endpoint_auth_method' => 'client_secret_post',
            'subject_type' => 'public',
            'post_logout_redirect_uris' => [],
        ]);
    });
});

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeDeveloper(): User
{
    return User::factory()->developer()->create();
}

function makeFirstPartyApp(User $user, array $overrides = []): App
{
    return App::factory()->for($user, 'owner')->firstParty()->create($overrides);
}

function makeThirdPartyApp(User $user, array $overrides = []): App
{
    return App::factory()->for($user, 'owner')->thirdParty()->create($overrides);
}

function makeDelivery(App $app, array $overrides = []): WebhookDelivery
{
    return WebhookDelivery::create(array_merge([
        'id' => (string) Str::ulid(),
        'app_id' => $app->id,
        'event' => 'user.updated',
        'url' => 'https://example.test/hook',
        'payload' => ['event' => 'user.updated', 'subject' => '1', 'changed' => []],
        'signature' => 'v1,abc123',
        'status' => 'delivered',
        'attempts' => 1,
        'response_code' => 200,
        'created_at' => now(),
    ], $overrides));
}

// ── Webhooks section access ───────────────────────────────────────────────────

test('webhooks section is 403 for third-party app owner', function () {
    $user = makeDeveloper();
    $app = makeThirdPartyApp($user);

    $this->actingAs($user)
        ->get(route('developers.webhooks', $app))
        ->assertForbidden();
});

test('webhooks section is 200 for first-party app owner and renders Webhooks component', function () {
    $user = makeDeveloper();
    $app = makeFirstPartyApp($user);

    $this->actingAs($user)
        ->get(route('developers.webhooks', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppDetail/Webhooks', false)
            ->has('app')
        );
});

test('webhooks section is 403 for non-owner of first-party app', function () {
    $otherUser = makeDeveloper();
    $app = makeFirstPartyApp(makeDeveloper());

    $this->actingAs($otherUser)
        ->get(route('developers.webhooks', $app))
        ->assertForbidden();
});

// ── PUT webhooks.update ───────────────────────────────────────────────────────

test('webhooks update persists config and seeds secret on first save', function () {
    $user = makeDeveloper();
    $app = makeFirstPartyApp($user, ['webhook_secret' => null]);

    $this->actingAs($user)->put(route('developers.webhooks.update', $app), [
        'webhook_url' => 'https://hooks.example.com/receive',
        'webhook_event_name' => 'Member Updated',
        'webhook_subscribed_fields' => ['email', 'username'],
    ])->assertRedirect(route('developers.webhooks', $app));

    $app->refresh();
    expect($app->webhook_url)->toBe('https://hooks.example.com/receive');
    expect($app->webhook_event_name)->toBe('Member Updated');
    expect($app->webhook_subscribed_fields)->toBe(['email', 'username']);
    // Secret seeded on first save when URL is present
    expect($app->webhook_secret)->not->toBeNull();
});

test('webhooks update is 403 for third-party app', function () {
    $user = makeDeveloper();
    $app = makeThirdPartyApp($user);

    $this->actingAs($user)->put(route('developers.webhooks.update', $app), [
        'webhook_url' => 'https://hooks.example.com/receive',
    ])->assertForbidden();
});

test('webhooks update does not overwrite existing secret', function () {
    $user = makeDeveloper();
    $existingSecret = bin2hex(random_bytes(16));
    $app = makeFirstPartyApp($user, ['webhook_secret' => $existingSecret]);

    $this->actingAs($user)->put(route('developers.webhooks.update', $app), [
        'webhook_url' => 'https://hooks.example.com/receive',
        'webhook_subscribed_fields' => ['email'],
    ])->assertRedirect();

    $app->refresh();
    // Secret must not have changed
    expect($app->webhook_secret)->toBe($existingSecret);
});

// ── POST reveal-secret ────────────────────────────────────────────────────────

test('reveal secret returns plaintext JSON for authorized owner', function () {
    $user = makeDeveloper();
    $secret = bin2hex(random_bytes(32));
    $app = makeFirstPartyApp($user, ['webhook_secret' => $secret]);

    $response = $this->actingAs($user)
        ->postJson(route('developers.webhooks.reveal-secret', $app));

    $response->assertOk()->assertJsonStructure(['secret']);
    expect($response->json('secret'))->toBe($secret);
});

test('reveal secret returns 403 for non-owner', function () {
    $otherUser = makeDeveloper();
    $app = makeFirstPartyApp(makeDeveloper(), [
        'webhook_secret' => bin2hex(random_bytes(32)),
    ]);

    $this->actingAs($otherUser)
        ->postJson(route('developers.webhooks.reveal-secret', $app))
        ->assertForbidden();
});

test('reveal secret writes an activity log entry', function () {
    $user = makeDeveloper();
    $app = makeFirstPartyApp($user, ['webhook_secret' => bin2hex(random_bytes(32))]);

    $this->actingAs($user)
        ->postJson(route('developers.webhooks.reveal-secret', $app))
        ->assertOk();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'webhook.secret.revealed',
        'subject_id' => $app->id,
        'subject_type' => App::class,
    ]);
});

// ── POST rotate-secret ────────────────────────────────────────────────────────

test('rotate secret issues a new secret different from the old one', function () {
    $user = makeDeveloper();
    $oldSecret = bin2hex(random_bytes(32));
    $app = makeFirstPartyApp($user, ['webhook_secret' => $oldSecret]);

    $response = $this->actingAs($user)
        ->postJson(route('developers.webhooks.rotate-secret', $app));

    $response->assertOk()->assertJsonStructure(['secret']);
    $newSecret = $response->json('secret');

    expect($newSecret)->not->toBe($oldSecret);
    $app->refresh();
    expect($app->webhook_secret)->toBe($newSecret);
});

test('old signature does not verify with new secret after rotation', function () {
    $user = makeDeveloper();
    $oldSecret = bin2hex(random_bytes(32));
    $app = makeFirstPartyApp($user, ['webhook_secret' => $oldSecret]);

    $signer = app(WebhookSigner::class);
    $timestamp = now()->timestamp;
    $body = json_encode(['event' => 'user.updated']);
    $oldSignature = $signer->sign($oldSecret, $timestamp, $body);

    // Rotate
    $response = $this->actingAs($user)
        ->postJson(route('developers.webhooks.rotate-secret', $app));
    $newSecret = $response->json('secret');

    // Old signature must NOT verify with new secret
    $stillValid = $signer->verify($newSecret, $timestamp, $body, $oldSignature, 300);
    expect($stillValid)->toBeFalse();
});

// ── POST test (send test delivery) ───────────────────────────────────────────

test('send test delivery returns 200 JSON with delivery_id and creates a WebhookDelivery row', function () {
    Queue::fake();

    $user = makeDeveloper();
    $app = makeFirstPartyApp($user, [
        'webhook_url' => 'https://hooks.example.com/receive',
        'webhook_secret' => bin2hex(random_bytes(32)),
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('developers.webhooks.test', $app));

    $response->assertOk()->assertJsonStructure(['delivery_id']);
    $deliveryId = $response->json('delivery_id');

    $this->assertDatabaseHas('webhook_deliveries', [
        'id' => $deliveryId,
        'app_id' => $app->id,
        'event' => 'user.updated',
    ]);
});

test('send test delivery returns 422 when app has no webhook URL', function () {
    $user = makeDeveloper();
    $app = makeFirstPartyApp($user, [
        'webhook_url' => null,
        'webhook_secret' => null,
    ]);

    $this->actingAs($user)
        ->postJson(route('developers.webhooks.test', $app))
        ->assertUnprocessable();
});

// ── GET deliveries ────────────────────────────────────────────────────────────

test('deliveries endpoint returns paginated JSON with correct shape', function () {
    $user = makeDeveloper();
    $app = makeFirstPartyApp($user);

    // Create 3 deliveries
    foreach (range(1, 3) as $i) {
        makeDelivery($app, ['created_at' => now()->subMinutes($i)]);
    }

    $response = $this->actingAs($user)
        ->getJson(route('developers.webhooks.deliveries', $app));

    $response->assertOk();
    $data = $response->json();

    // Laravel pagination wraps in data key
    expect($data)->toHaveKey('data');
    expect(count($data['data']))->toBe(3);
    // Each delivery has expected keys
    expect($data['data'][0])->toHaveKeys(['id', 'app_id', 'event', 'url', 'status']);
});

test('deliveries endpoint returns 403 for third-party app', function () {
    $user = makeDeveloper();
    $app = makeThirdPartyApp($user);

    $this->actingAs($user)
        ->getJson(route('developers.webhooks.deliveries', $app))
        ->assertForbidden();
});

test('deliveries endpoint only returns deliveries for the requested app', function () {
    $user = makeDeveloper();
    $app = makeFirstPartyApp($user);
    $otherApp = makeFirstPartyApp(makeDeveloper());

    makeDelivery($app);
    makeDelivery($otherApp); // should not appear

    $response = $this->actingAs($user)
        ->getJson(route('developers.webhooks.deliveries', $app));

    $response->assertOk();
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['app_id'])->toBe($app->id);
});

// ── POST redeliver ────────────────────────────────────────────────────────────

test('redeliver creates a new delivery row with the same payload but a different ID', function () {
    Queue::fake();

    $user = makeDeveloper();
    $app = makeFirstPartyApp($user, [
        'webhook_url' => 'https://hooks.example.com/receive',
        'webhook_secret' => bin2hex(random_bytes(32)),
    ]);
    $original = makeDelivery($app);

    $response = $this->actingAs($user)
        ->postJson(route('developers.webhooks.redeliver', ['app' => $app->id, 'delivery' => $original->id]));

    $response->assertOk()->assertJsonStructure(['delivery_id']);
    $newId = $response->json('delivery_id');

    expect($newId)->not->toBe($original->id);

    $newDelivery = WebhookDelivery::find($newId);
    expect($newDelivery)->not->toBeNull();
    expect($newDelivery->payload)->toEqual($original->payload);
    expect($newDelivery->app_id)->toBe($app->id);
});

test('redeliver returns 404 for delivery belonging to a different app', function () {
    $user = makeDeveloper();
    $app = makeFirstPartyApp($user, [
        'webhook_url' => 'https://hooks.example.com/receive',
        'webhook_secret' => bin2hex(random_bytes(32)),
    ]);
    $otherApp = makeFirstPartyApp(makeDeveloper(), [
        'webhook_url' => 'https://other.example.com/hook',
        'webhook_secret' => bin2hex(random_bytes(32)),
    ]);
    $delivery = makeDelivery($otherApp);

    // Need to also give the other app's owner access so the 403 doesn't fire first
    // Here we test with the first app owner trying to redeliver the other app's delivery
    $this->actingAs($user)
        ->postJson(route('developers.webhooks.redeliver', ['app' => $app->id, 'delivery' => $delivery->id]))
        ->assertNotFound();
});
