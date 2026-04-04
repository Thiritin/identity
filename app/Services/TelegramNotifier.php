<?php

namespace App\Services;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class TelegramNotifier
{
    public function __construct(private Nutgram $bot) {}

    public function notifyLogin(User $user, ?string $ipAddress = null): void
    {
        $this->send($user, $this->buildLoginMessage($ipAddress));
    }

    public function notifyPasswordChanged(User $user): void
    {
        $this->send($user, $this->buildPasswordChangedMessage());
    }

    public function notifyEmailChanged(User $user, string $oldEmail, string $newEmail): void
    {
        $this->send($user, $this->buildEmailChangedMessage($oldEmail, $newEmail));
    }

    private function send(User $user, string $message): void
    {
        if (! $user->telegram_id) {
            return;
        }

        try {
            $this->bot->sendMessage(
                text: $message,
                chat_id: $user->telegram_id,
                parse_mode: ParseMode::MARKDOWN,
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function buildLoginMessage(?string $ipAddress): string
    {
        $message = "🔐 *Security Alert*\n\nA new login to your Eurofurence Identity account was detected.";

        if ($ipAddress) {
            $message .= "\n\nIP: `{$ipAddress}`";
        }

        $message .= "\n\nIf this wasn't you, change your password immediately.";

        return $message;
    }

    private function buildPasswordChangedMessage(): string
    {
        return "🔐 *Security Alert*\n\nYour Eurofurence Identity password was changed.\n\nIf this wasn't you, contact support immediately.";
    }

    private function buildEmailChangedMessage(string $oldEmail, string $newEmail): string
    {
        return "🔐 *Security Alert*\n\nYour Eurofurence Identity email was changed from `{$oldEmail}` to `{$newEmail}`.\n\nIf this wasn't you, contact support immediately.";
    }
}
