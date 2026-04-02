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
        $timeout = config('auth.sudo_timeout', 1800);
        $confirmedAt = $request->session()->get('auth.password_confirmed_at', 0);
        $needsConfirmation = (Date::now()->unix() - $confirmedAt) > $timeout;

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
