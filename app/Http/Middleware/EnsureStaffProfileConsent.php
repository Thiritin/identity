<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class EnsureStaffProfileConsent
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isStaff()) {
            abort(403);
        }

        if (! $user->hasStaffProfileConsent()) {
            throw ValidationException::withMessages([
                '_consent' => [trans('staff_profile_consent_required_error')],
            ]);
        }

        return $next($request);
    }
}
