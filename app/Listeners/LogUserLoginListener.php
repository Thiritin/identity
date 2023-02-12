<?php

namespace App\Listeners;

class LogUserLoginListener
{
    public function __construct()
    {
    }

    public function handle($event)
    {
        activity()
            ->causedBy($event->user)
            ->log('login');
    }
}
