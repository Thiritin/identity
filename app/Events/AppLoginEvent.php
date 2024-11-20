<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AppLoginEvent
{
    use Dispatchable;

    public function __construct(readonly public string $clientId, readonly public string $userId) {}
}
