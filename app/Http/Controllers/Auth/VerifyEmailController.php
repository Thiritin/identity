<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailVerificationRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class VerifyEmailController extends Controller
{
    public function view(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Redirect::route('auth.choose');
        }

        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(EmailVerificationRequest $request)
    {
        $user = $request->verifyUser();
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        // No login session? The user gets redirected to the success page.
        return Inertia::render('Auth/VerifyEmailSuccess', ['user' => $user->only('name', 'email')]);
    }

    public function resend(Request $request)
    {
        $success = \Illuminate\Support\Facades\RateLimiter::attempt($request->user()->id . ':resend-verification-email',
            1,
            function () use ($request) {
                if ($request->user()->hasVerifiedEmail()) {
                    return Redirect::route('dashboard');
                }
                $request->user()->sendEmailVerificationNotification();

                return true;
            }, 30);
        if (! $success) {
            return Inertia::render('Auth/VerifyEmail')->with('status', 'throttled');
        }

        return Inertia::render('Auth/VerifyEmail')->with('status', 'verification-link-sent');
    }
}
