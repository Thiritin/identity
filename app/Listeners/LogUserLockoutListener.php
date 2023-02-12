<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Lockout;

class LogUserLockoutListener
{
    public function __construct()
    {
    }

    public function handle(Lockout $event)
    {
        activity()
            ->causedBy($event->user)
            ->log('lockout');
    }
}
