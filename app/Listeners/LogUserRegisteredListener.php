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
            ->causedBy($event->user)
            ->log('registered');
    }
}
