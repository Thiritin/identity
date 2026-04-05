<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            '*/admin/clients*' => Http::response([
                'client_id' => 'test-client-' . uniqid(),
                'client_secret' => 'test-secret',
                'client_name' => 'Test App',
                'redirect_uris' => ['https://example.com/callback'],
                'grant_types' => ['authorization_code'],
                'response_types' => ['code'],
                'scope' => 'openid',
                'token_endpoint_auth_method' => 'client_secret_post',
                'subject_type' => 'public',
                'post_logout_redirect_uris' => [],
            ]),
        ]);
    }

    public function test_dispatches_for_first_party_app_when_subscribed_field_changed(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $app = App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email', 'username'],
        ]);

        $user = User::factory()->create(['email' => 'new@example.com', 'name' => 'new_handle']);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            $user,
            oldValues: ['email' => 'old@example.com'],
            changedExternalFields: ['email'],
        );

        Queue::assertPushedOn('webhooks', DeliverWebhook::class);
        $this->assertDatabaseHas('webhook_deliveries', [
            'app_id' => $app->id,
            'event' => 'user.updated',
            'status' => 'pending',
            'url' => 'https://example.test/hook',
        ]);

        $delivery = WebhookDelivery::first();
        $this->assertSame(['email'], array_keys($delivery->payload['changed']));
        $this->assertSame('old@example.com', $delivery->payload['changed']['email']['old']);
        $this->assertSame('new@example.com', $delivery->payload['changed']['email']['new']);
    }

    public function test_skips_third_party_apps(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => false,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'],
        ]);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            User::factory()->create(),
            oldValues: ['email' => 'old@example.com'],
            changedExternalFields: ['email'],
        );

        Queue::assertNothingPushed();
    }

    public function test_skips_apps_without_webhook_url(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => null,
            'webhook_subscribed_fields' => ['email'],
        ]);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            User::factory()->create(),
            oldValues: ['email' => 'old@example.com'],
            changedExternalFields: ['email'],
        );

        Queue::assertNothingPushed();
    }

    public function test_skips_apps_without_field_intersection(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['username'],
        ]);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            User::factory()->create(),
            oldValues: ['email' => 'old@example.com'],
            changedExternalFields: ['email'],
        );

        Queue::assertNothingPushed();
    }

    public function test_payload_is_filtered_to_subscribed_intersection(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'], // only email
        ]);

        $user = User::factory()->create(['email' => 'new@example.com', 'name' => 'new_handle']);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            $user,
            oldValues: ['email' => 'old@example.com', 'name' => 'old_handle'],
            changedExternalFields: ['email', 'username'], // both changed but app only subscribed to email
        );

        $delivery = WebhookDelivery::first();
        $this->assertSame(['email'], array_keys($delivery->payload['changed']));
    }
}
