<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;

class LogFailedLoginListener
{
    public function __construct() {}

    public function handle(Failed $event)
    {
        activity()
            ->causedBy($event->user)
            ->log('login-failed');
    }
}
