<?php

namespace Tests\Feature\Webhooks;

use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneWebhookDeliveriesTest extends TestCase
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

    public function test_prunes_rows_older_than_seven_days(): void
    {
        $owner = User::factory()->create();
        $app = App::factory()->for($owner, 'owner')->create(['first_party' => true]);

        $old = WebhookDelivery::create([
            'id' => (string) Str::ulid(), 'app_id' => $app->id, 'event' => 'user.updated',
            'url' => 'https://x', 'payload' => [], 'signature' => 'v1,a', 'status' => 'delivered',
            'attempts' => 1, 'created_at' => now()->subDays(8),
        ]);
        $fresh = WebhookDelivery::create([
            'id' => (string) Str::ulid(), 'app_id' => $app->id, 'event' => 'user.updated',
            'url' => 'https://x', 'payload' => [], 'signature' => 'v1,a', 'status' => 'delivered',
            'attempts' => 1, 'created_at' => now()->subDays(6),
        ]);

        $this->artisan('webhooks:prune-deliveries')->assertSuccessful();

        $this->assertDatabaseMissing('webhook_deliveries', ['id' => $old->id]);
        $this->assertDatabaseHas('webhook_deliveries', ['id' => $fresh->id]);
    }
}
