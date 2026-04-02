<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\App;
use App\Models\User;
use App\Services\Hydra\Client;
use App\Services\Hydra\HydraRequestException;
use GrantHolle\Altcha\Rules\ValidAltcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Log;

class LoginController extends Controller
{
    public function view(Request $request)
    {
        $loginChallenge = Session::get('auth.login_challenge.challenge');

        if (! $loginChallenge) {
            return Redirect::route('auth.login.view');
        }

        $email = Session::get('auth.email_flow.email');

        try {
            $hydra = new Client();
            $loginRequest = $hydra->getLoginRequest($loginChallenge);
        } catch (HydraRequestException $e) {
            Log::warning('Login challenge expired or invalid, restarting login flow', [
                'challenge' => $loginChallenge,
                'message' => $e->getMessage(),
            ]);
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
            $skipUser = User::findByHashid($subject);
            if ($skipUser && $skipUser->isSuspended()) {
                return Redirect::route('auth.error', [
                    'error' => 'account_suspended',
                    'error_description' => trans('account_suspended'),
                ]);
            }
        }

        if ($subject !== null) {
            $emailVerified = $this->checkEmailVerification($loginRequest, $subject);
            if ($emailVerified === false) {
                return Redirect::route('login.apps.redirect', ['app' => 'portal']);
            }
        }

        if ($subject !== null) {
            return Redirect::to($hydra->acceptLogin($subject, $loginRequest['challenge'], null, $loginRequest));
        }

        if (! $email) {
            return Redirect::route('auth.login.view');
        }

        // If password was pre-filled by a password manager, attempt login directly
        $prefilledPassword = Session::pull('auth.email_flow.password');
        if ($prefilledPassword) {
            $result = $this->attemptLogin($request, $email, $prefilledPassword, $loginChallenge);
            if ($result) {
                return $result;
            }
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

        $loginChallenge = Session::get('auth.login_challenge.challenge');

        if (! $loginChallenge) {
            return Redirect::route('auth.login.view');
        }

        $result = $this->attemptLogin(
            request: $request,
            email: $request->get('email'),
            password: $request->get('password'),
            loginChallenge: $loginChallenge,
            remember: $request->boolean('remember'),
        );

        if ($result) {
            return $result;
        }

        throw ValidationException::withMessages(['nouser' => 'Wrong details']);
    }

    /**
     * Attempt to authenticate a user and handle the post-login flow.
     *
     * @return RedirectResponse|Response|null Redirect on success, null on failure
     */
    private function attemptLogin(Request $request, string $email, string $password, string $loginChallenge, bool $remember = false)
    {
        if (Auth::once(['email' => $email, 'password' => $password]) !== true) {
            RateLimiter::hit('login-pow:' . $request->ip(), 60 * 60 * 24);

            return null;
        }

        $user = Auth::user();

        if ($user->isSuspended()) {
            return Redirect::route('auth.error', [
                'error' => 'account_suspended',
                'error_description' => trans('account_suspended'),
            ]);
        }

        try {
            $hydra = new Client();
            $loginRequest = $hydra->getLoginRequest($loginChallenge);
        } catch (HydraRequestException $e) {
            Log::warning('Login challenge expired during authentication', [
                'challenge' => $loginChallenge,
                'message' => $e->getMessage(),
            ]);
            Session::forget('auth.email_flow');
            Session::forget('auth.login_challenge');

            return Redirect::route('auth.login.view');
        }

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
                'remember' => $remember,
            ], now()->addMinutes(30));
        }

        try {
            $url = (new Client())->acceptLogin($user->hashid, $loginChallenge,
                $remember ? '2592000' : '3600');
        } catch (HydraRequestException $e) {
            Log::warning('Failed to accept login with Hydra', [
                'challenge' => $loginChallenge,
                'message' => $e->getMessage(),
            ]);
            Session::forget('auth.email_flow');
            Session::forget('auth.login_challenge');

            return Redirect::route('auth.login.view');
        }

        RateLimiter::clear('login-pow:' . $request->ip());

        Session::forget('auth.email_flow');
        Session::forget('auth.login_challenge');

        return Inertia::location($url);
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
        $clientModel = App::where('client_id', $loginRequest['client']['client_id'])->firstOrFail();
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
