<?php

use App\Jobs\SendAppNotificationJob;
use App\Models\App;
use App\Models\NotificationType;
use App\Models\User;
use App\Services\Auth\ApiGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\ValidatesOpenApiV2;

uses(RefreshDatabase::class, ValidatesOpenApiV2::class);

function actingAsClient(string $clientId, array $scopes = []): void
{
    // The auth:api middleware requires check() to be true, which means a user
    // must exist. For client-credentials-style endpoints we still stub a user
    // because the framework's Authenticate middleware gates on it, but the
    // controller only cares about the client id and scopes.
    $stubUser = User::factory()->create();

    $guard = Mockery::mock(ApiGuard::class);
    $guard->shouldReceive('user')->andReturn($stubUser);
    $guard->shouldReceive('check')->andReturn(true);
    $guard->shouldReceive('guest')->andReturn(false);
    $guard->shouldReceive('id')->andReturn($stubUser->id);
    $guard->shouldReceive('hasUser')->andReturn(true);
    $guard->shouldReceive('getClientId')->andReturn($clientId);
    $guard->shouldReceive('getScopes')->andReturn($scopes);
    $guard->shouldReceive('setRequest')->andReturnSelf();

    Auth::extend('hydra', fn () => $guard);
    Auth::forgetGuards();
}

beforeEach(function () {
    Cache::flush();
    Queue::fake();
    Http::fake([
        '*admin/clients*' => Http::response([
            'client_id' => 'app-one',
            'client_secret' => 'stubbed-secret',
        ], 200),
    ]);
});

it('queues a notification on happy path', function () {
    $app = App::factory()->create([
        'client_id' => 'app-one',
        'allow_notifications' => true,
    ]);
    $type = NotificationType::factory()->create([
        'app_id' => $app->id,
        'key' => 'welcome',
    ]);
    $user = User::factory()->create();

    actingAsClient('app-one', ['notifications.send']);

    $response = $this->postJson('/api/v2/notifications', [
        'type' => 'welcome',
        'user_id' => $user->hashid,
        'subject' => 'Hi',
        'body' => 'Welcome!',
    ])->assertStatus(202);

    Queue::assertPushed(SendAppNotificationJob::class);

    $this->assertMatchesOpenApiV2($response, '/notifications', 'post');
});

it('returns 403 when app does not have allow_notifications enabled', function () {
    $app = App::factory()->create([
        'client_id' => 'app-one',
        'allow_notifications' => false,
    ]);
    NotificationType::factory()->create(['app_id' => $app->id, 'key' => 'welcome']);
    $user = User::factory()->create();

    actingAsClient('app-one', ['notifications.send']);

    $response = $this->postJson('/api/v2/notifications', [
        'type' => 'welcome',
        'user_id' => $user->hashid,
        'subject' => 'Hi',
        'body' => 'Hello',
    ])->assertStatus(403);

    Queue::assertNothingPushed();

    $this->assertMatchesOpenApiV2($response, '/notifications', 'post');
});

it('returns 403 when scope missing', function () {
    $app = App::factory()->create([
        'client_id' => 'app-one',
        'allow_notifications' => true,
    ]);
    NotificationType::factory()->create(['app_id' => $app->id, 'key' => 'welcome']);
    $user = User::factory()->create();

    actingAsClient('app-one', []);

    $response = $this->postJson('/api/v2/notifications', [
        'type' => 'welcome',
        'user_id' => $user->hashid,
        'subject' => 'Hi',
        'body' => 'Hello',
    ])->assertStatus(403);

    $this->assertMatchesOpenApiV2($response, '/notifications', 'post');
});

it('returns 404 when type key does not exist for this app', function () {
    App::factory()->create(['client_id' => 'app-one', 'allow_notifications' => true]);
    $user = User::factory()->create();

    actingAsClient('app-one', ['notifications.send']);

    $response = $this->postJson('/api/v2/notifications', [
        'type' => 'missing',
        'user_id' => $user->hashid,
        'subject' => 'Hi',
        'body' => 'Hello',
    ])->assertStatus(404);

    $this->assertMatchesOpenApiV2($response, '/notifications', 'post');
});

it('returns 404 when type is disabled', function () {
    $app = App::factory()->create(['client_id' => 'app-one', 'allow_notifications' => true]);
    NotificationType::factory()->create([
        'app_id' => $app->id,
        'key' => 'welcome',
        'disabled' => true,
    ]);
    $user = User::factory()->create();

    actingAsClient('app-one', ['notifications.send']);

    $response = $this->postJson('/api/v2/notifications', [
        'type' => 'welcome',
        'user_id' => $user->hashid,
        'subject' => 'Hi',
        'body' => 'Hello',
    ])->assertStatus(404);

    $this->assertMatchesOpenApiV2($response, '/notifications', 'post');
});

it('returns 404 when user hashid does not decode to an existing user', function () {
    $app = App::factory()->create(['client_id' => 'app-one', 'allow_notifications' => true]);
    NotificationType::factory()->create(['app_id' => $app->id, 'key' => 'welcome']);

    actingAsClient('app-one', ['notifications.send']);

    $response = $this->postJson('/api/v2/notifications', [
        'type' => 'welcome',
        'user_id' => 'NOTAREALHASHID',
        'subject' => 'Hi',
        'body' => 'Hello',
    ])->assertStatus(404);

    $this->assertMatchesOpenApiV2($response, '/notifications', 'post');
});

it('rejects missing required fields with 422', function () {
    $app = App::factory()->create(['client_id' => 'app-one', 'allow_notifications' => true]);
    NotificationType::factory()->create(['app_id' => $app->id, 'key' => 'welcome']);

    actingAsClient('app-one', ['notifications.send']);

    $response = $this->postJson('/api/v2/notifications', [
        'type' => 'welcome',
    ])->assertStatus(422);

    $this->assertMatchesOpenApiV2($response, '/notifications', 'post');
});

it('rejects partial cta with 422', function () {
    $app = App::factory()->create(['client_id' => 'app-one', 'allow_notifications' => true]);
    NotificationType::factory()->create(['app_id' => $app->id, 'key' => 'welcome']);
    $user = User::factory()->create();

    actingAsClient('app-one', ['notifications.send']);

    $response = $this->postJson('/api/v2/notifications', [
        'type' => 'welcome',
        'user_id' => $user->hashid,
        'subject' => 'Hi',
        'body' => 'Hello',
        'cta' => ['label' => 'Click'],
    ])->assertStatus(422);

    $this->assertMatchesOpenApiV2($response, '/notifications', 'post');
});

it('enforces rate limit of 60 per minute per app', function () {
    $app = App::factory()->create(['client_id' => 'app-one', 'allow_notifications' => true]);
    NotificationType::factory()->create(['app_id' => $app->id, 'key' => 'welcome']);
    $user = User::factory()->create();

    actingAsClient('app-one', ['notifications.send']);

    // Raise the global api throttle so it doesn't interfere with the
    // notifications-specific limiter under test.
    RateLimiter::for('api', fn () => Limit::perMinute(10000));

    for ($i = 0; $i < 60; $i++) {
        $this->postJson('/api/v2/notifications', [
            'type' => 'welcome',
            'user_id' => $user->hashid,
            'subject' => 'Hi',
            'body' => 'Hello',
        ])->assertStatus(202);
    }

    $response = $this->postJson('/api/v2/notifications', [
        'type' => 'welcome',
        'user_id' => $user->hashid,
        'subject' => 'Hi',
        'body' => 'Hello',
    ])->assertStatus(429)->assertHeader('Retry-After');

    $this->assertMatchesOpenApiV2($response, '/notifications', 'post');
});
