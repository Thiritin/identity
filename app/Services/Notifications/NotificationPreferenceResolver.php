<?php

namespace App\Services\Notifications;

use App\Enums\NotificationCategory;
use App\Models\NotificationType;
use App\Models\User;

class NotificationPreferenceResolver
{
    /**
     * @return array<int, string> Channel names to deliver on.
     */
    public function resolve(User $user, NotificationType $type): array
    {
        $defaults = $type->default_channels;

        // Transactional: bypass all user preferences.
        if ($type->category === NotificationCategory::Transactional) {
            return array_values(array_unique($defaults));
        }

        $prefs = $user->notification_preferences ?? [];
        $masterChannels = $prefs['channels'] ?? [];
        $typeOverrides = $prefs['types'][(string) $type->id] ?? null;

        $working = [];

        foreach ($defaults as $channel) {
            // Step 2: apply type override if present, else keep default.
            if (is_array($typeOverrides) && array_key_exists($channel, $typeOverrides)) {
                if ((bool) $typeOverrides[$channel] === false) {
                    continue;
                }
            }

            // Step 3: apply master switch.
            if (array_key_exists($channel, $masterChannels) && (bool) $masterChannels[$channel] === false) {
                continue;
            }

            $working[] = $channel;
        }

        return array_values(array_unique($working));
    }
}
