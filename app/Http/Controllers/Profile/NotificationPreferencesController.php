<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateNotificationPreferencesRequest;
use App\Models\NotificationType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferencesController extends Controller
{
    public function edit(): Response
    {
        $types = NotificationType::with('app:id,name')
            ->where('disabled', false)
            ->whereHas('app', fn ($q) => $q->where('allow_notifications', true))
            ->orderBy('app_id')
            ->orderBy('category')
            ->get()
            ->groupBy('app.name');

        return Inertia::render('Settings/Notifications', [
            'preferences' => auth()->user()->notification_preferences ?? [
                'channels' => ['email' => true, 'telegram' => true, 'database' => true],
                'types' => [],
            ],
            'groupedTypes' => $types,
        ]);
    }

    public function update(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $channels = $request->input('channels');
        $typeOverrides = $request->input('types', []);

        // Start from existing preferences so overrides for disabled types
        // (not rendered in the UI) are preserved.
        $existing = $user->notification_preferences['types'] ?? [];
        $merged = $existing;

        $typeIds = array_keys($typeOverrides);
        $types = NotificationType::whereIn('id', $typeIds)->get()->keyBy('id');

        foreach ($typeOverrides as $id => $overrides) {
            $type = $types->get((int) $id);
            if (! $type) {
                continue;
            }
            $filtered = [];
            foreach ($type->default_channels as $channel) {
                if (array_key_exists($channel, $overrides)) {
                    $filtered[$channel] = (bool) $overrides[$channel];
                }
            }
            if (! empty($filtered)) {
                $merged[(string) $id] = $filtered;
            } else {
                unset($merged[(string) $id]);
            }
        }

        $user->notification_preferences = [
            'channels' => $channels,
            'types' => $merged,
        ];
        $user->save();

        return back();
    }
}
