<?php

namespace App\Listeners;

use Illuminate\Auth\Events\PasswordReset;

class LogUserPasswordResetListener
{
    public function __construct()
    {
    }

    public function handle(PasswordReset $event)
    {
        activity()
            ->causedBy($event->user)
            ->log('password-reset');
    }
}
