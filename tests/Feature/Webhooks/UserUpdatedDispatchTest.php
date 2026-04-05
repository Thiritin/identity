<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UserUpdatedDispatchTest extends TestCase
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

    public function test_email_change_creates_delivery(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'],
        ]);

        $user = User::factory()->create(['email' => 'old@example.com']);
        $user->update(['email' => 'new@example.com']);

        Queue::assertPushed(DeliverWebhook::class);
        $delivery = WebhookDelivery::firstOrFail();
        $this->assertSame('old@example.com', $delivery->payload['changed']['email']['old']);
        $this->assertSame('new@example.com', $delivery->payload['changed']['email']['new']);
    }

    public function test_username_change_uses_name_column(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['username'],
        ]);

        $user = User::factory()->create(['name' => 'old_handle']);
        $user->update(['name' => 'new_handle']);

        $delivery = WebhookDelivery::firstOrFail();
        $this->assertSame('old_handle', $delivery->payload['changed']['username']['old']);
        $this->assertSame('new_handle', $delivery->payload['changed']['username']['new']);
    }

    public function test_unrelated_field_does_not_dispatch(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email', 'username'],
        ]);

        $user = User::factory()->create();
        $user->update(['firstname' => 'Jane']); // not in allowlist

        Queue::assertNothingPushed();
        $this->assertSame(0, WebhookDelivery::count());
    }

    public function test_created_user_does_not_dispatch(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'],
        ]);

        User::factory()->create();

        Queue::assertNothingPushed();
    }
}
