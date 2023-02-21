<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

class LogUserRegisteredListener
{
    public function __construct()
    {
    }

    public function handle(Registered $event)
    {
        activity()
            ->by($event->user)
            ->log('registered');
        activity()
            ->by($event->user)
            ->log('mail-verify-email');
    }
}
