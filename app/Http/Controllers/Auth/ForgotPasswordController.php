<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Password;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $key = 'reset-passwords:'.$request->ip();
        // Throttle requests
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts.']);
        }

        RateLimiter::hit($key);

        $request->validate([
            "email" => "email|required|exists:users,email"
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        activity()
            ->byAnonymous()
            ->on(User::whereEmail($request->get('email'))->firstOrFail())
            ->log('mail-reset-password');

        return $status === Password::RESET_LINK_SENT
            ? Inertia::render('Auth/ForgotPassword', ['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }
}
