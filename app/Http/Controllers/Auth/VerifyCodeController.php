<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyEmailCodeNotification;
use App\Services\Hydra\Client;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class VerifyCodeController extends Controller
{
    public function view()
    {
        $verifyData = Session::get('auth.verify_code');

        if (! $verifyData) {
            return Redirect::route('auth.login.view');
        }

        return Inertia::render('Auth/VerifyCode');
    }

    public function submit(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $verifyData = Session::get('auth.verify_code');

        if (! $verifyData) {
            return Redirect::route('auth.login.view');
        }

        if (now()->isAfter($verifyData['expires_at'])) {
            return back()->withErrors(['code' => __('verify_code_expired')]);
        }

        if (strtoupper($request->code) !== $verifyData['code']) {
            return back()->withErrors(['code' => __('verify_code_invalid')]);
        }

        $user = User::findOrFail($verifyData['user_id']);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        Session::forget('auth.verify_code');

        return $this->acceptLoginChallenge($user);
    }

    public function resend()
    {
        $verifyData = Session::get('auth.verify_code');

        if (! $verifyData) {
            return Redirect::route('auth.login.view');
        }

        $user = User::findOrFail($verifyData['user_id']);

        $code = substr(str_shuffle(str_repeat('ABCDEFGHJKMNPQRSTUVWXYZ23456789', 3)), 0, 6);
        Session::put('auth.verify_code', [
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new VerifyEmailCodeNotification($code));

        return back()->with('status', 'code-resent');
    }

    private function acceptLoginChallenge(User $user)
    {
        $challengeData = Session::get('auth.login_challenge');

        if (! $challengeData) {
            Session::put('justRegisteredSkipLogin.user_id', $user->id);

            return Redirect::route('login.apps.redirect', ['app' => 'portal']);
        }

        try {
            $hydra = new Client();
            $loginRequest = $hydra->getLoginRequest($challengeData['challenge']);

            if (isset($loginRequest['redirect_to'])) {
                Session::forget('auth.login_challenge');
                Session::forget('auth.email_flow');

                return Redirect::route('auth.error', [
                    'error' => 'login_expired',
                    'error_description' => 'Your login session has expired. Please try again.',
                ]);
            }

            $url = $hydra->acceptLogin($user->hashid, $challengeData['challenge'], '3600');

            Session::forget('auth.login_challenge');
            Session::forget('auth.email_flow');

            return Inertia::location($url);
        } catch (\Exception $e) {
            Session::forget('auth.login_challenge');
            Session::forget('auth.email_flow');

            return Redirect::route('auth.error', [
                'error' => 'login_failed',
                'error_description' => 'Login could not be completed. Please try again.',
            ]);
        }
    }
}
