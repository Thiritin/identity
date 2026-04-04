<?php

use App\Telegram\Handlers\FallbackHandler;
use App\Telegram\Handlers\StartHandler;
use App\Telegram\Handlers\UnlinkHandler;
use SergiX44\Nutgram\Nutgram;

/** @var Nutgram $bot */
$bot->onCommand('start {code}', StartHandler::class);
$bot->onCommand('start', StartHandler::class);
$bot->onCommand('unlink', UnlinkHandler::class);
$bot->fallback(FallbackHandler::class);
