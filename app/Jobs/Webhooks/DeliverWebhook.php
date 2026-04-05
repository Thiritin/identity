<?php

namespace App\Jobs\Webhooks;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    public int $timeout = 20;

    public function __construct(public string $deliveryId, public int $timestamp)
    {
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300, 1800, 7200, 21600];
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::with('app')->find($this->deliveryId);
        if (! $delivery || $delivery->status === 'delivered' || ! $delivery->app) {
            return;
        }

        $body = json_encode($delivery->payload, JSON_THROW_ON_ERROR);

        try {
            /** @var Response $response */
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'User-Agent' => 'EF-Identity-Webhooks/1.0',
                'X-EF-Event' => $delivery->event,
                'X-EF-Delivery' => $delivery->id,
                'X-EF-Timestamp' => (string) $this->timestamp,
                'X-EF-Signature' => $delivery->signature,
            ])
                ->connectTimeout(8)
                ->timeout(15)
                ->withBody($body, 'application/json')
                ->post($delivery->url);

            $delivery->attempts = $this->attempts();
            $delivery->response_code = $response->status();
            $delivery->response_body = $this->truncate((string) $response->body());

            if ($response->successful()) {
                $delivery->status = 'delivered';
                $delivery->delivered_at = now();
                $delivery->save();
                return;
            }

            $this->markRetryingOrThrow($delivery, 'HTTP ' . $response->status());
        } catch (Throwable $e) {
            $delivery->attempts = $this->attempts();
            $delivery->error = $e->getMessage();
            $this->markRetryingOrThrow($delivery, $e->getMessage(), rethrow: $e);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $delivery = WebhookDelivery::find($this->deliveryId);
        if (! $delivery) {
            return;
        }
        $delivery->status = 'failed';
        if ($exception && ! $delivery->error) {
            $delivery->error = $exception->getMessage();
        }
        $delivery->save();
    }

    private function markRetryingOrThrow(WebhookDelivery $delivery, string $reason, ?Throwable $rethrow = null): void
    {
        // NOTE: when $attempts >= $tries we intentionally do NOT touch $delivery->status here.
        // We still rethrow so the worker records the failure; the failed() hook then marks
        // status=failed after the final attempt. Do not "fix" this by setting status='failed'
        // in this branch — it would race with failed() and lose the final error message.
        $attempts = $this->attempts();
        if ($attempts < $this->tries) {
            $delivery->status = 'retrying';
            $nextBackoff = $this->backoff()[$attempts] ?? 21600;
            $delivery->next_retry_at = now()->addSeconds($nextBackoff);
        }
        $delivery->save();

        throw $rethrow ?? new \RuntimeException($reason);
    }

    private function truncate(string $body): string
    {
        if (strlen($body) <= 2048) {
            return $body;
        }
        return substr($body, 0, 2048) . '…(truncated)';
    }
}
