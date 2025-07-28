<?php

namespace App\Domains\Admin\Http\Middleware;

use Filament\Http\Middleware\Authenticate;

class AuthenticateMiddleware extends Authenticate
{
    protected function redirectTo($request): ?string
    {
        return route('login.apps.redirect', ['app' => 'admin']);
    }
}
