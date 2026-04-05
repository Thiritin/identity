<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\UpdateAppWebhookRequest;
use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\WebhookSigner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AppWebhookController extends Controller
{
    public function show(App $app)
    {
        Gate::authorize('manageWebhooks', $app);

        return Inertia::render('Settings/Apps/AppDetail/Webhooks', [
            'app' => [
                'id' => $app->id,
                'client_id' => $app->client_id,
                'first_party' => $app->isFirstParty(),
                'webhook_url' => $app->webhook_url,
                'webhook_event_name' => $app->webhook_event_name,
                'webhook_subscribed_fields' => $app->webhook_subscribed_fields ?? [],
                'has_secret' => (bool) $app->webhook_secret,
            ],
        ]);
    }

    public function update(UpdateAppWebhookRequest $request, App $app)
    {
        Gate::authorize('manageWebhooks', $app);

        $validated = $request->validated();

        $app->webhook_url = $validated['webhook_url'] ?? null;
        $app->webhook_event_name = $validated['webhook_event_name'] ?? null;
        $app->webhook_subscribed_fields = $validated['webhook_subscribed_fields'] ?? [];

        if ($app->webhook_url && ! $app->webhook_secret) {
            $app->webhook_secret = bin2hex(random_bytes(32));
        }

        $app->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('apps_webhook_saved')]);

        return redirect()->route('developers.webhooks', $app);
    }

    public function revealSecret(App $app)
    {
        Gate::authorize('viewWebhookSecret', $app);
        activity()->performedOn($app)->causedBy(auth()->user())->log('webhook.secret.revealed');
        return response()->json(['secret' => $app->webhook_secret]);
    }

    public function rotateSecret(App $app)
    {
        Gate::authorize('manageWebhooks', $app);
        $app->webhook_secret = bin2hex(random_bytes(32));
        $app->save();
        activity()->performedOn($app)->causedBy(auth()->user())->log('webhook.secret.rotated');
        return response()->json(['secret' => $app->webhook_secret]);
    }

    public function sendTest(App $app, WebhookSigner $signer)
    {
        Gate::authorize('manageWebhooks', $app);
        abort_unless($app->webhook_url && $app->webhook_secret, 422, 'Webhook URL and secret must be saved first.');

        $user = auth()->user();
        $payload = [
            'event' => 'user.updated',
            'id' => (string) Str::ulid(),
            'occurred_at' => now()->toIso8601String(),
            'subject' => (string) $user->getKey(),
            'changed' => [
                'email' => ['old' => $user->email, 'new' => $user->email],
            ],
            'test' => true,
        ];

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $signature = $signer->sign($app->webhook_secret, $timestamp, $body);

        $delivery = WebhookDelivery::create([
            'id' => $payload['id'],
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

        return response()->json(['delivery_id' => $delivery->id]);
    }

    public function deliveries(Request $request, App $app)
    {
        Gate::authorize('manageWebhooks', $app);

        $deliveries = $app->webhookDeliveries()
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($deliveries);
    }

    public function redeliver(App $app, WebhookDelivery $delivery, WebhookSigner $signer)
    {
        Gate::authorize('manageWebhooks', $app);
        abort_unless($delivery->app_id === $app->id, 404);

        $timestamp = now()->timestamp;
        $body = json_encode($delivery->payload, JSON_THROW_ON_ERROR);
        $signature = $signer->sign($app->webhook_secret, $timestamp, $body);

        $new = WebhookDelivery::create([
            'id' => (string) Str::ulid(),
            'app_id' => $app->id,
            'event' => $delivery->event,
            'url' => $app->webhook_url,
            'payload' => $delivery->payload,
            'signature' => $signature,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => now(),
        ]);

        DeliverWebhook::dispatch($new->id, $timestamp)->onQueue('webhooks');

        return response()->json(['delivery_id' => $new->id]);
    }
}
