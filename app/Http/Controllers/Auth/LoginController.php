<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TwoFactorTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\App;
use App\Models\User;
use App\Services\Hydra\Client;
use App\Services\WebAuthnService;
use GrantHolle\Altcha\Rules\ValidAltcha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Log;

class LoginController extends Controller
{
    public function view(Request $request)
    {
        $email = Session::get('auth.email_flow.email');
        $loginChallenge = Session::get('auth.login_challenge.challenge');

        if (! $loginChallenge) {
            return Redirect::route('auth.login.view');
        }

        if (! $email) {
            $subject = Session::get('auth.login_challenge.subject', '');
            $skipUser = $subject ? User::findByHashid($subject) : null;
            if ($skipUser) {
                $email = $skipUser->email;
                Session::put('auth.email_flow.email', $email);
            } else {
                return Redirect::route('auth.login.view');
            }
        }

        try {
            $hydra = new Client();
            $loginRequest = $hydra->getLoginRequest($loginChallenge);

            if (isset($loginRequest['error'])) {
                throw new \RuntimeException($loginRequest['error_description'] ?? 'Invalid login challenge');
            }
        } catch (\Exception $e) {
            Session::forget('auth.email_flow');
            Session::forget('auth.login_challenge');

            return Redirect::route('auth.login.view');
        }

        // redirect_to key is added when login request expired.
        if (isset($loginRequest['redirect_to'])) {
            return Redirect::to($loginRequest['redirect_to']);
        }

        $user = User::where('email', $email)->first();

        // Centralized enforcement checks (suspension + staff TOTP)
        if ($user) {
            $enforcement = $this->checkLoginEnforcement($user, $loginRequest);
            if ($enforcement) {
                return $enforcement;
            }
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

        $hasPasskeys = $user ? $user->twoFactors()->where('type', TwoFactorTypeEnum::PASSKEY)->exists() : false;

        return Inertia::render('Auth/Login', [
            'email' => $email,
            'requiresPow' => $requiresPow,
            'hasPasskeys' => $hasPasskeys,
        ]);
    }

    public function passkeyOptions(Request $request): JsonResponse
    {
        $email = Session::get('auth.email_flow.email');
        $loginChallenge = Session::get('auth.login_challenge.challenge');

        if (! $email || ! $loginChallenge) {
            return response()->json(['error' => 'No active login session'], 400);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $webAuthnService = new WebAuthnService();
        $options = $webAuthnService->generateAuthenticationOptions($user, TwoFactorTypeEnum::PASSKEY);

        return response()->json($options);
    }

    public function passkeyVerify(Request $request)
    {
        $request->validate([
            'credential' => 'required|string',
        ]);

        $email = Session::get('auth.email_flow.email');
        $loginChallenge = Session::get('auth.login_challenge.challenge');

        if (! $email || ! $loginChallenge) {
            return Redirect::route('auth.login.view');
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['credential' => 'User not found']);
        }

        $limitKey = 'passkey-login-' . $user->id;
        if (RateLimiter::tooManyAttempts($limitKey, 10)) {
            throw ValidationException::withMessages(['credential' => 'Too many attempts. Please try again later.']);
        }
        RateLimiter::hit($limitKey, 120);

        try {
            $webAuthnService = new WebAuthnService();
            $webAuthnService->verifyAuthentication($user, $request->input('credential'), TwoFactorTypeEnum::PASSKEY);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['credential' => $e->getMessage()]);
        }

        RateLimiter::clear($limitKey);

        $hydra = new Client();
        $loginRequest = $hydra->getLoginRequest($loginChallenge);

        if (isset($loginRequest['redirect_to'])) {
            return Redirect::to($loginRequest['redirect_to']);
        }

        $enforcement = $this->checkLoginEnforcement($user, $loginRequest);
        if ($enforcement) {
            return $enforcement;
        }

        $emailVerified = $this->checkEmailVerification($loginRequest, $user);
        if ($emailVerified === false) {
            return Redirect::route('login.apps.redirect', ['app' => 'portal']);
        }

        // Check for 2FA (passkey replaces password only, not 2FA)
        $has2fa = $user->twoFactors()
            ->whereIn('type', [TwoFactorTypeEnum::TOTP, TwoFactorTypeEnum::YUBIKEY, TwoFactorTypeEnum::SECURITY_KEY])
            ->exists();

        if ($has2fa) {
            return Redirect::signedRoute('auth.two-factor', [
                'login_challenge' => $loginChallenge,
                'user' => $user->hashid,
            ], now()->addMinutes(30));
        }

        Session::put('auth.pending_login', [
            'user_hashid' => $user->hashid,
            'login_challenge' => $loginChallenge,
            'clear_pow_ip' => $request->ip(),
        ]);

        return Redirect::route('auth.remember-session');
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

            $enforcement = $this->checkLoginEnforcement($user, $loginRequest);
            if ($enforcement) {
                return $enforcement;
            }

            $emailVerified = $this->checkEmailVerification($loginRequest, $user);
            if ($emailVerified === false) {
                return Redirect::route('login.apps.redirect', ['app' => 'portal']);
            }

            if ($user->twoFactors()->whereIn('type', [TwoFactorTypeEnum::TOTP, TwoFactorTypeEnum::YUBIKEY, TwoFactorTypeEnum::SECURITY_KEY])->exists()) {
                return Redirect::signedRoute('auth.two-factor', [
                    'login_challenge' => $loginChallenge,
                    'user' => $user->hashid,
                ], now()->addMinutes(30));
            }

            Session::put('auth.pending_login', [
                'user_hashid' => $user->hashid,
                'login_challenge' => $loginChallenge,
                'clear_pow_ip' => $request->ip(),
            ]);

            return Redirect::route('auth.remember-session');
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

    private function isPortalClient(array $loginRequest): bool
    {
        $clientId = $loginRequest['client']['client_id'] ?? null;
        if (! $clientId) {
            return false;
        }

        return App::where('client_id', $clientId)->where('system_name', 'portal')->exists();
    }

    private function checkLoginEnforcement(User $user, array $loginRequest): ?InertiaResponse
    {
        if ($user->isSuspended()) {
            return Inertia::render('Auth/Suspended');
        }

        if ($user->isStaff() && ! $this->isPortalClient($loginRequest)) {
            $hasTotp = $user->twoFactors()->where('type', TwoFactorTypeEnum::TOTP)->exists();
            if (! $hasTotp) {
                return Inertia::render('Auth/StaffTotpRequired', [
                    'portalLoginUrl' => route('login.apps.redirect', ['app' => 'portal']),
                ]);
            }
        }

        return null;
    }

    private function checkEmailVerification($loginRequest, User|string $user)
    {
        // Get App
        $clientModel = App::where('client_id', $loginRequest['client']['client_id'])->first();

        if (! $clientModel) {
            $clientId = $loginRequest['client']['client_id'] ?? 'unknown';
            Log::error('OAuth client not found in database', [
                'client_id' => $clientId,
                'available_apps' => App::pluck('client_id')->toArray(),
            ]);

            throw new \RuntimeException("OAuth client not configured: {$clientId}");
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
