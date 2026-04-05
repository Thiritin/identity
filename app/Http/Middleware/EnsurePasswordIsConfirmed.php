<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        $now = Date::now()->unix();
        $timeout = config('auth.sudo_timeout', 1800);
        $loginGrace = config('auth.login_sudo_grace', 300);

        $confirmedAt = $request->session()->get('auth.password_confirmed_at', 0);
        $loggedInAt = $request->session()->get('auth.logged_in_at', 0);

        $withinConfirmWindow = ($now - $confirmedAt) <= $timeout;
        $withinLoginGrace = $loginGrace > 0 && ($now - $loggedInAt) <= $loginGrace;

        $needsConfirmation = ! $withinConfirmWindow && ! $withinLoginGrace;

        if ($needsConfirmation) {
            if (! $request->isMethod('GET')) {
                return response()->json([
                    'message' => 'Password confirmation required.',
                ], 423);
            }

            Inertia::share('passwordConfirmRequired', true);
        }

        return $next($request);
    }
}
