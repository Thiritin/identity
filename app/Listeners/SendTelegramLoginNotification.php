<?php

namespace App\Listeners;

use App\Services\TelegramNotifier;
use Illuminate\Auth\Events\Login;

class SendTelegramLoginNotification
{
    public function __construct(private TelegramNotifier $notifier) {}

    public function handle(Login $event): void
    {
        $this->notifier->notifyLogin($event->user, request()?->ip());
    }
}
