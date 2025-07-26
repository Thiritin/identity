<?php

namespace App\Listeners;

use App\Events\AppLoginEvent;
use App\Domains\User\Models\App;
use App\Domains\User\Models\User;

class LogUserAppLoginListener
{
    public function __construct() {}

    public function handle(AppLoginEvent $event)
    {
        activity()
            ->causedBy(User::findByHashid($event->userId))
            ->performedOn(App::firstWhere('client_id', $event->clientId))
            ->log('login-app');
    }
}
