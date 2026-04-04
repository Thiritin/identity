<?php

namespace App\Notifications\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class AppTelegramChannel
{
    public function __construct(private Nutgram $bot) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            return;
        }

        if (! $notifiable->telegram_id) {
            logger()->info('telegram delivery skipped: user has no telegram_id', [
                'user_id' => $notifiable->id,
            ]);
            return;
        }

        if (! method_exists($notification, 'toAppTelegram')) {
            return;
        }

        $message = $notification->toAppTelegram($notifiable);

        try {
            $this->bot->sendMessage(
                text: $message,
                chat_id: $notifiable->telegram_id,
                parse_mode: ParseMode::MARKDOWN,
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
