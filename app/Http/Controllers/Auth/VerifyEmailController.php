<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailVerificationRequest;
use App\Services\Hydra\Client;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class VerifyEmailController extends Controller
{
    public function view(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Redirect::route('auth.oidc.login');
        }
        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(EmailVerificationRequest $request)
    {
        $user = $request->verifyUser();
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));

            // If there is a cached login session for the user, use it.
            $loginChallenge = Cache::get("web." . $user->id . ".loginChallenge");

            // If this is anything else than null, then accept the prevalidated request once
            if (!is_null($loginChallenge)) {
                $hydra = new Client();
                Cache::delete("web." . $user->id . ".loginChallenge");
                return Inertia::location($hydra->acceptLogin($user->hashid(), $loginChallenge));
            }
        }
        // No login session? The user gets redirected to the success page.
        return Inertia::render('Auth/VerifyEmailSuccess', ['user' => $user->only('name', 'email')]);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Redirect::route('dashboard');
        }
        $request->user()->sendEmailVerificationNotification();

        return Inertia::render('Auth/VerifyEmail')->with('status', 'verification-link-sent');
    }
}
