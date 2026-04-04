<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class UnlinkHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $telegramId = $bot->user()?->id;

        if (! $telegramId) {
            return;
        }

        $user = User::where('telegram_id', $telegramId)->first();

        if (! $user) {
            $bot->sendMessage(text: 'Your Telegram account is not linked to any account.');

            return;
        }

        $user->telegram_id = null;
        $user->telegram_username = null;
        $user->save();

        $bot->sendMessage(text: 'Your Telegram account has been unlinked.');
    }
}
