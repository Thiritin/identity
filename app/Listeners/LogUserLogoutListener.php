<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogUserLogoutListener
{
    public function __construct()
    {
    }

    public function handle(Logout $event)
    {
        activity()
            ->causedBy($event->user)
            ->log('logout');
    }
}
