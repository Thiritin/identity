<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\User\Models\User;
use App\Domains\Auth\Services\Client;
use App\Domains\Auth\Services\SecurityNotificationService;
use App\Domains\Auth\Services\WebAuthn\WebAuthnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ModernLoginController extends Controller
{
    private WebAuthnService $webauthnService;

    public function __construct(WebAuthnService $webauthnService)
    {
        $this->webauthnService = $webauthnService;
    }

    public function view(Request $request)
    {
        if ($request->missing('login_challenge')) {
            return Redirect::route('auth.choose');
        }

        $hydra = new Client();
        $loginRequest = $hydra->getLoginRequest($request->get('login_challenge'));

        if (isset($loginRequest['redirect_to'])) {
            return Redirect::to($loginRequest['redirect_to']);
        }

        // Check if user should skip login
        $subject = $this->shouldSkipLogin($loginRequest);
        if ($subject !== null) {
            $emailVerified = $this->checkEmailVerification($loginRequest, $subject);
            if ($emailVerified === false) {
                return Redirect::route('login.apps.redirect', ['app' => 'portal']);
            }
            return Redirect::to($hydra->acceptLogin($subject, $loginRequest['challenge'], null, $loginRequest));
        }

        // Start with identity step
        return Inertia::render('Auth/ModernLogin', [
            'step' => 'identify',
            'login_challenge' => $request->get('login_challenge')
        ]);
    }

    public function identifyUser(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:255',
            'login_challenge' => 'required|string'
        ]);

        $identifier = $request->get('identifier');
        
        // Find user by email or username
        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();
        
        if (!$user) {
            throw ValidationException::withMessages([
                'identifier' => 'No account found with this email address or username.'
            ]);
        }

        // Store user info in session for next step
        Session::put('modern_login.user_id', $user->id);
        Session::put('modern_login.login_challenge', $request->get('login_challenge'));

        // Determine available authentication methods
        $hasPassword = !empty($user->password);
        $hasWebauthn = $user->hasWebauthnCredentials();

        if (!$hasPassword && !$hasWebauthn) {
            throw ValidationException::withMessages([
                'identifier' => 'No authentication method available for this account.'
            ]);
        }

        // Generate WebAuthn options if available
        $webauthnOptions = null;
        if ($hasWebauthn) {
            $webauthnOptions = $this->webauthnService->generateAuthenticationOptions($user);
            Session::put('modern_login.webauthn_options', serialize($webauthnOptions));
        }

        return Inertia::render('Auth/ModernLogin', [
            'step' => 'authenticate',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->profile_photo_path
            ],
            'authMethods' => [
                'password' => $hasPassword,
                'webauthn' => $hasWebauthn
            ],
            'webauthnOptions' => $webauthnOptions ? [
                'challenge' => base64_encode($webauthnOptions->getChallenge()),
                'allowCredentials' => array_map(function ($cred) {
                    return [
                        'type' => $cred->getType(),
                        'id' => base64_encode($cred->getId()),
                        'transports' => $cred->getTransports()
                    ];
                }, $webauthnOptions->getAllowCredentials()),
                'userVerification' => $webauthnOptions->getUserVerification(),
                'timeout' => $webauthnOptions->getTimeout()
            ] : null
        ]);
    }

    public function authenticatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'stay_signed_in' => 'boolean'
        ]);

        $userId = Session::get('modern_login.user_id');
        $loginChallenge = Session::get('modern_login.login_challenge');

        if (!$userId || !$loginChallenge) {
            return $this->resetLoginFlow();
        }

        $user = User::findOrFail($userId);

        if (!Hash::check($request->get('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The password is incorrect.'
            ]);
        }

        return $this->completeAuthentication($user, $loginChallenge, $request->get('stay_signed_in', false));
    }

    public function authenticateWebauthn(Request $request)
    {
        $request->validate([
            'credential' => 'required|array',
            'stay_signed_in' => 'boolean'
        ]);

        $userId = Session::get('modern_login.user_id');
        $loginChallenge = Session::get('modern_login.login_challenge');
        $webauthnOptions = Session::get('modern_login.webauthn_options');

        if (!$userId || !$loginChallenge || !$webauthnOptions) {
            return $this->resetLoginFlow();
        }

        $user = User::findOrFail($userId);
        $options = unserialize($webauthnOptions);

        $authenticatedUser = $this->webauthnService->verifyAuthentication(
            $request->get('credential'),
            $options,
            $user
        );

        if (!$authenticatedUser) {
            throw ValidationException::withMessages([
                'webauthn' => 'Authentication failed. Please try again.'
            ]);
        }

        // Notify of passwordless login
        app(SecurityNotificationService::class)->notifyPasswordlessLogin(
            $user,
            'WebAuthn/Passkey',
            $request->userAgent(),
            $request->ip()
        );

        return $this->completeAuthentication($user, $loginChallenge, $request->get('stay_signed_in', false));
    }

    private function completeAuthentication(User $user, string $loginChallenge, bool $staySignedIn)
    {
        // Clear session data
        Session::forget(['modern_login.user_id', 'modern_login.login_challenge', 'modern_login.webauthn_options']);

        $hydra = new Client();
        $loginRequest = $hydra->getLoginRequest($loginChallenge);

        if (isset($loginRequest['redirect_to'])) {
            return Redirect::to($loginRequest['redirect_to']);
        }

        // Check email verification
        $emailVerified = $this->checkEmailVerification($loginRequest, $user);
        if ($emailVerified === false) {
            return Redirect::route('login.apps.redirect', ['app' => 'portal']);
        }

        // Check for 2FA
        if ($user->twoFactors()->exists()) {
            return Redirect::signedRoute('auth.two-factor', [
                'login_challenge' => $loginChallenge,
                'user' => $user->hashid,
                'remember' => $staySignedIn,
            ], now()->addMinutes(30));
        }

        // Determine remember duration
        $rememberDuration = $staySignedIn ? 
            config('auth.remember_duration_extended', 2592000) : // 30 days
            config('auth.remember_duration_standard', 3600);     // 1 hour

        $url = $hydra->acceptLogin($user->hashId(), $loginChallenge, $rememberDuration);

        return Inertia::location($url);
    }

    private function resetLoginFlow()
    {
        Session::forget(['modern_login.user_id', 'modern_login.login_challenge', 'modern_login.webauthn_options']);
        
        return Redirect::route('auth.modern-login.view')->with('error', 'Session expired. Please start over.');
    }

    private function shouldSkipLogin(mixed $loginRequest): ?string
    {
        if (isset($loginRequest['skip']) && $loginRequest['skip'] === true) {
            return $loginRequest['subject'];
        }

        $registerLoginSkipOnceUserId = Session::get('justRegisteredSkipLogin.user_id');
        if (!is_null($registerLoginSkipOnceUserId)) {
            $subject = $registerLoginSkipOnceUserId;
            Session::forget('justRegisteredSkipLogin.user_id');
            return User::findOrFail($subject)->hashid;
        }

        return null;
    }

    private function checkEmailVerification($loginRequest, User|string $user)
    {
        $clientModel = \App\Domains\User\Models\App::where('client_id', $loginRequest['client']['client_id'])->firstOrFail();
        
        if (($user instanceof User) === false) {
            $user = User::findByHashidOrFail($user);
        }
        
        if ($user->hasVerifiedEmail() === true) {
            return true;
        }
        
        if ($clientModel->system_name === 'portal') {
            return true;
        }

        return false;
    }
}