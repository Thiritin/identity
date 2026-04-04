<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;

class FallbackHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $text = trim($bot->message()?->text ?? '');

        if (preg_match('/^[A-Za-z0-9]{6}$/', $text)) {
            (new LinkHandler())($bot, $text);

            return;
        }

        $bot->sendMessage(
            text: "I don't understand that. To link your account, visit your profile settings and click 'Connect Telegram'."
        );
    }
}
