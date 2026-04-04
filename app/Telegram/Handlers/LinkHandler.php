<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;

class LinkHandler
{
    public function __invoke(Nutgram $bot, string $code): void
    {
        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z0-9]{6}$/', $code)) {
            return;
        }

        $userId = Cache::get("telegram_link:{$code}");

        if (! $userId) {
            $bot->sendMessage(
                text: 'This code is invalid or has expired. Please generate a new code from your profile settings.'
            );

            return;
        }

        $username = $bot->user()?->username;

        if (! $username) {
            $bot->sendMessage(
                text: "You need to set a Telegram username before linking.\n\nGo to Telegram Settings > Username to set one, then try again."
            );

            return;
        }

        $telegramId = $bot->user()->id;

        $existingUser = User::where('telegram_id', $telegramId)->first();

        if ($existingUser && $existingUser->id !== $userId) {
            $bot->sendMessage(
                text: 'This Telegram account is already linked to another user. Please unlink it first using /unlink.'
            );

            return;
        }

        $user = User::findOrFail($userId);
        $user->telegram_id = $telegramId;
        $user->telegram_username = $username;
        $user->save();

        Cache::forget("telegram_link:{$code}");
        Cache::forget("telegram_link_user:{$userId}");

        $bot->sendMessage(
            text: "You have been successfully linked with {$user->name} on Eurofurence Identity!"
        );
    }
}
