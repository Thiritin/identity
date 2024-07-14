<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\App;
use App\Models\User;
use App\Services\Hydra\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function view(Request $request)
    {
        if ($request->missing('login_challenge')) {
            return Redirect::route('auth.choose');
        }

        $hydra = new Client();
        $loginRequest = $hydra->getLoginRequest($request->get('login_challenge'));

        // redirect_to key is added when login request expired.
        if (isset($loginRequest['redirect_to'])) {
            return Redirect::to($loginRequest['redirect_to']);
        }
        // Check if user is allowed to skip login
        $subject = $this->shouldSkipLogin($loginRequest);

        if ($subject !== null) {
            $emailVerified = $this->checkEmailVerification($loginRequest, $subject); // Check if user has verified email
            if ($emailVerified === false) {
                return Redirect::route('login.apps.redirect', ['app' => 'portal']);
            }
        }

        if ($subject !== null) {
            return Redirect::to($hydra->acceptLogin($subject, $loginRequest["challenge"], 3600,
                $loginRequest));
        }

        return Inertia::render('Auth/Login');
    }

    public function submit(LoginRequest $request)
    {
        $loginData = [
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ];

        if (Auth::once($loginData) === true) {
            $user = Auth::user();
            
            $hydra = new Client();
            $loginRequest = $hydra->getLoginRequest($request->get('login_challenge'));

            // redirect_to key is added when login request expired.
            if (isset($loginRequest['redirect_to'])) {
                return Redirect::to($loginRequest['redirect_to']);
            }

            $emailVerified = $this->checkEmailVerification($loginRequest, $user); // Check if user has verified email
            if ($emailVerified === false) {
                return Redirect::route('login.apps.redirect', ['app' => 'portal']);
            }

            if ($user->twoFactors()->exists()) {
                return Redirect::signedRoute('auth.two-factor', [
                    'login_challenge' => $request->get('login_challenge'),
                    'user' => $user->hashid,
                    'remember' => $request->get('remember') ?? false,
                ], now()->addMinutes(30));
            }

            $url = (new Client())->acceptLogin($user->hashId(), $request->get('login_challenge'),
                $request->get('remember') ? "2592000" : "3600");

            return Inertia::location($url);
        }

        throw ValidationException::withMessages(['nouser' => 'Wrong details']);
    }

    /**
     * @param  mixed  $loginRequest
     * @return string|null Subject of the user to skip login for
     */
    private function shouldSkipLogin(mixed $loginRequest): ?string
    {
        if (isset($loginRequest["skip"]) && $loginRequest["skip"] === true) {
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
