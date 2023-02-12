<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AppLoginEvent
{
    use Dispatchable;

    public string $clientId;
    public string $userId;

    public function __construct(string $clientId, string $userId)
    {

        $this->clientId = $clientId;
        $this->userId = $userId;
    }
}
