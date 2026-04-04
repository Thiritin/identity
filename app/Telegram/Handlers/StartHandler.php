<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;

class StartHandler
{
    public function __invoke(Nutgram $bot, ?string $code = null): void
    {
        if (! $code) {
            $bot->sendMessage(
                text: "Welcome to the Eurofurence bot!\n\nTo link your Telegram account, visit your profile settings and click 'Connect Telegram'."
            );

            return;
        }

        (new LinkHandler())($bot, $code);
    }
}
