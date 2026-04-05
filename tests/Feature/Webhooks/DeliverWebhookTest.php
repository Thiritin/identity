<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeliverWebhookTest extends TestCase
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

    private function makeDelivery(array $overrides = []): WebhookDelivery
    {
        $owner = User::factory()->create();
        $app = App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'],
        ]);

        return WebhookDelivery::create(array_merge([
            'id' => (string) Str::ulid(),
            'app_id' => $app->id,
            'event' => 'user.updated',
            'url' => $app->webhook_url,
            'payload' => ['event' => 'user.updated', 'changed' => []],
            'signature' => 'v1,abc',
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => now(),
        ], $overrides));
    }

    public function test_2xx_marks_delivered(): void
    {
        Http::fake(['example.test/*' => Http::response('ok', 200)]);
        $delivery = $this->makeDelivery();

        (new DeliverWebhook($delivery->id, now()->timestamp))->handle();

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(200, $delivery->response_code);
        $this->assertNotNull($delivery->delivered_at);
    }

    public function test_5xx_marks_retrying_and_throws(): void
    {
        Http::fake(['example.test/*' => Http::response('boom', 500)]);
        $delivery = $this->makeDelivery();

        $job = new DeliverWebhook($delivery->id, now()->timestamp);

        $this->expectException(\RuntimeException::class);
        $job->handle();

        $delivery->refresh();
        $this->assertSame('retrying', $delivery->status);
        $this->assertSame(500, $delivery->response_code);
        $this->assertStringContainsString('boom', $delivery->response_body);
    }

    public function test_response_body_truncated_to_2048(): void
    {
        Http::fake(['example.test/*' => Http::response(str_repeat('x', 5000), 200)]);
        $delivery = $this->makeDelivery();
        (new DeliverWebhook($delivery->id, now()->timestamp))->handle();

        $delivery->refresh();
        $this->assertLessThanOrEqual(2100, strlen($delivery->response_body));
        $this->assertStringEndsWith('(truncated)', $delivery->response_body);
    }

    public function test_failed_hook_marks_failed(): void
    {
        $delivery = $this->makeDelivery(['status' => 'retrying', 'attempts' => 5]);
        (new DeliverWebhook($delivery->id, now()->timestamp))->failed(new \RuntimeException('exhausted'));

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertStringContainsString('exhausted', $delivery->error);
    }

    public function test_skips_when_already_delivered(): void
    {
        $delivery = $this->makeDelivery(['status' => 'delivered', 'delivered_at' => now()]);

        // Re-fake after makeDelivery() to reset recorded requests; only the webhook target is faked now
        Http::fake(['example.test/*' => Http::response('ok', 200)]);

        (new DeliverWebhook($delivery->id, now()->timestamp))->handle();

        Http::assertNothingSent();
    }
}
