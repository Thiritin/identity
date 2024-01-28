<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GuardSwitcher
{
    public function handle(Request $request, Closure $next, $defaultGuard = null)
    {
        if (array_key_exists($defaultGuard, config("auth.guards"))) {
            config(["auth.defaults.guard" => $defaultGuard]);
        }
        return $next($request);
    }
}
