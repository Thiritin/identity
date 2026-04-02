<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ConfirmPasswordController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $key = 'confirm-password:' . $request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'password' => trans('too_many_attempts', ['seconds' => $seconds]),
            ]);
        }

        RateLimiter::hit($key, 60);

        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        RateLimiter::clear($key);
        $request->session()->passwordConfirmed();

        return Inertia::back();
    }
}
