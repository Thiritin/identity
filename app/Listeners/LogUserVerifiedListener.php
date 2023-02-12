<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;

class LogUserVerifiedListener
{
    public function __construct()
    {
    }

    public function handle(Verified $event)
    {
        activity()
            ->causedBy($event->user)
            ->log('verified');
    }
}
