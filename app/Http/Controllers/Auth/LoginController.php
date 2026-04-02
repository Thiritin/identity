<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\App;
use App\Models\User;
use App\Services\Hydra\Client;
use GrantHolle\Altcha\Rules\ValidAltcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Log;

class LoginController extends Controller
{
    public function view(Request $request)
    {
        $email = Session::get('auth.email_flow.email');
        $loginChallenge = Session::get('auth.login_challenge.challenge');

        if (! $email || ! $loginChallenge) {
            return Redirect::route('auth.login.view');
        }

        try {
            $hydra = new Client();
            $loginRequest = $hydra->getLoginRequest($loginChallenge);
        } catch (\Exception $e) {
            Session::forget('auth.email_flow');
            Session::forget('auth.login_challenge');

            return Redirect::route('auth.login.view');
        }

        // redirect_to key is added when login request expired.
        if (isset($loginRequest['redirect_to'])) {
            return Redirect::to($loginRequest['redirect_to']);
        }

        // Check if user is allowed to skip login
        $subject = $this->shouldSkipLogin($loginRequest);

        if ($subject !== null) {
            $emailVerified = $this->checkEmailVerification($loginRequest, $subject);
            if ($emailVerified === false) {
                return Redirect::route('login.apps.redirect', ['app' => 'portal']);
            }
        }

        if ($subject !== null) {
            return Redirect::to($hydra->acceptLogin($subject, $loginRequest['challenge'], null, $loginRequest));
        }

        $requiresPow = RateLimiter::tooManyAttempts('login-pow:' . $request->ip(), 3);

        return Inertia::render('Auth/Login', [
            'email' => $email,
            'requiresPow' => $requiresPow,
        ]);
    }

    public function submit(LoginRequest $request)
    {
        Log::info('Login attempt', [
            'ip' => $request->ip(),
            'x-forwarded-for' => $request->header('X-Forwarded-For'),
            'x-real-ip' => $request->header('X-Real-IP'),
            'remote_addr' => $request->server('REMOTE_ADDR'),
        ]);

        if (RateLimiter::tooManyAttempts('login-pow:' . $request->ip(), 3)) {
            $request->validate([
                'altcha' => ['required', new ValidAltcha()],
            ], [
                'altcha.required' => trans('pow_required'),
            ]);
        }

        $loginData = [
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ];

        $loginChallenge = Session::get('auth.login_challenge.challenge');

        if (! $loginChallenge) {
            return Redirect::route('auth.login.view');
        }

        if (Auth::once($loginData) === true) {
            $user = Auth::user();

            $hydra = new Client();
            $loginRequest = $hydra->getLoginRequest($loginChallenge);

            // redirect_to key is added when login request expired.
            if (isset($loginRequest['redirect_to'])) {
                return Redirect::to($loginRequest['redirect_to']);
            }

            $emailVerified = $this->checkEmailVerification($loginRequest, $user);
            if ($emailVerified === false) {
                return Redirect::route('login.apps.redirect', ['app' => 'portal']);
            }

            if ($user->twoFactors()->exists()) {
                return Redirect::signedRoute('auth.two-factor', [
                    'login_challenge' => $loginChallenge,
                    'user' => $user->hashid,
                    'remember' => $request->get('remember') ?? false,
                ], now()->addMinutes(30));
            }

            $url = (new Client())->acceptLogin($user->hashId(), $loginChallenge,
                $request->get('remember') ? '2592000' : '3600');

            RateLimiter::clear('login-pow:' . $request->ip());

            Session::forget('auth.email_flow');
            Session::forget('auth.login_challenge');

            return Inertia::location($url);
        }

        RateLimiter::hit('login-pow:' . $request->ip(), 60 * 60 * 24);

        throw ValidationException::withMessages(['nouser' => 'Wrong details']);
    }

    /**
     * @param  mixed  $loginRequest
     * @return string|null Subject of the user to skip login for
     */
    private function shouldSkipLogin(mixed $loginRequest): ?string
    {
        if (isset($loginRequest['skip']) && $loginRequest['skip'] === true) {
            return $loginRequest['subject'];
        }

        $registerLoginSkipOnceUserId = Session::get('justRegisteredSkipLogin.user_id');
        if (! is_null($registerLoginSkipOnceUserId)) {
            $subject = $registerLoginSkipOnceUserId;
            Session::forget('justRegisteredSkipLogin.user_id');

            return User::findOrFail($subject)->hashid;
        }

        return null;
    }

    private function checkEmailVerification($loginRequest, User|string $user)
    {
        // Get App
        $clientModel = App::where('client_id', $loginRequest['client']['client_id'])->first();

        if (! $clientModel) {
            Session::forget('auth.email_flow');
            Session::forget('auth.login_challenge');

            return false;
        }
        // Get User
        if (($user instanceof User) === false) {
            $user = User::findByHashidOrFail($user);
        }
        // Check if user has verified email
        if ($user->hasVerifiedEmail() === true) {
            return true;
        }
        // If not, check if app system_name is portal then allow users to login
        if ($clientModel->system_name === 'portal') {
            return true;
        }

        return false;
    }
}
