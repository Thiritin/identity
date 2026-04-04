<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Password;

class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request)
    {
        $key = 'reset-passwords:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => __('passwords.throttled')]);
        }

        RateLimiter::hit($key);

        Password::sendResetLink(
            $request->only('email')
        );

        $user = User::whereEmail($request->get('email'))->first();

        if ($user) {
            activity()
                ->byAnonymous()
                ->on($user)
                ->log('mail-reset-password');
        }

        return Inertia::render('Auth/ForgotPassword', [
            'status' => __('passwords.sent'),
        ]);
    }
}
