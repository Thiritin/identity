<?php

namespace App\Notifications\Channels;

use App\Models\AppNotificationRecord;
use App\Models\User;
use Illuminate\Notifications\Notification;

class AppDatabaseChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            return;
        }

        if (! method_exists($notification, 'toAppDatabase')) {
            return;
        }

        $data = $notification->toAppDatabase($notifiable);

        try {
            AppNotificationRecord::create([
                'app_id' => $data['app_id'],
                'notification_type_id' => $data['notification_type_id'],
                'user_id' => $notifiable->id,
                'subject' => $data['subject'],
                'body' => $data['body'],
                'cta_label' => $data['cta_label'] ?? null,
                'cta_url' => $data['cta_url'] ?? null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
