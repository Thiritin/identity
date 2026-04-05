<?php

namespace App\Services\Webhooks;

use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Support\Str;

class WebhookDispatcher
{
    public function __construct(private readonly WebhookSigner $signer)
    {
    }

    /**
     * @param  array<string,mixed>  $oldValues  keyed by external field name (e.g. "email", "username")
     * @param  list<string>  $changedExternalFields
     */
    public function dispatchUserUpdated(User $user, array $oldValues, array $changedExternalFields): void
    {
        if ($changedExternalFields === []) {
            return;
        }

        $apps = App::query()
            ->where('first_party', true)
            ->whereNotNull('webhook_url')
            ->whereNotNull('webhook_secret')
            ->get();

        foreach ($apps as $app) {
            $subscribed = (array) ($app->webhook_subscribed_fields ?? []);
            $intersection = array_values(array_intersect($subscribed, $changedExternalFields));

            if ($intersection === []) {
                continue;
            }

            $changed = [];
            foreach ($intersection as $field) {
                $column = UserFieldMap::columnFor($field);
                if ($column === null) {
                    continue;
                }
                $changed[$field] = [
                    'old' => $oldValues[$field] ?? null,
                    'new' => $user->{$column},
                ];
            }

            if ($changed === []) {
                continue;
            }

            $deliveryId = (string) Str::ulid();

            $payload = [
                'event' => 'user.updated',
                'id' => $deliveryId,
                'occurred_at' => now()->toIso8601String(),
                'subject' => (string) $user->getKey(),
                'changed' => $changed,
            ];

            $body = json_encode($payload, JSON_THROW_ON_ERROR);
            $timestamp = now()->timestamp;
            $signature = $this->signer->sign($app->webhook_secret, $timestamp, $body);

            $delivery = WebhookDelivery::create([
                'id' => $deliveryId,
                'app_id' => $app->id,
                'event' => 'user.updated',
                'url' => $app->webhook_url,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'pending',
                'attempts' => 0,
                'created_at' => now(),
            ]);

            DeliverWebhook::dispatch($delivery->id, $timestamp)->onQueue('webhooks');
        }
    }
}
