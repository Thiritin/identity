<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AppLoginEvent
{
    use Dispatchable;

    public function __construct(public readonly string $clientId, public readonly string $userId) {}
}
