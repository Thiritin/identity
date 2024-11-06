<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Vinkla\Hashids\Facades\Hashids;

class AuthController extends Controller
{
    public function login($app)
    {
        $this->checkApp($app);
        // Redirect to the app if already logged in
        $guard = $this->getGuard($app);
        if (Auth::guard($guard)->check()) {
            return redirect()->route(config('services.apps')[$app]['home_route']);
        }

        $url = Socialite::driver('idp-' . $app)
            ->scopes(config('services.apps')[$app]['scopes'])
            ->redirect();

        return Inertia::location($url->getTargetUrl());
    }

    public function loginCallback($app)
    {
        $this->checkApp($app);
        try {
            $userInfo = Socialite::driver('idp-' . $app)->user();
        } catch (InvalidStateException $e) {
            return redirect()->route('login.apps.redirect', ['app' => $app]);
        }
        $userid = Hashids::connection('user')->decode($userInfo->id)[0];
        Auth::guard($this->getGuard($app))->loginUsingId($userid);
        Socialite::driver('idp-' . $app)->putToken(
            token: $userInfo->token,
            refreshToken: $userInfo->refreshToken,
            expiresIn: now()->addSeconds($userInfo->expiresIn),
        );

        return redirect()->route(config('services.apps')[$app]['home_route']);
    }

    public function logout($app)
    {
        $this->checkApp($app);

        return Socialite::driver('idp-' . $app)->logoutAll();
    }

    public function frontchannelLogout($app)
    {
        $this->checkApp($app);
        Auth::guard($this->getGuard($app))->logout();
        Socialite::driver('idp-' . $app)->clearToken();
    }

    private function checkApp($app)
    {
        if (in_array($app, ['staff', 'portal', 'admin']) === false) {
            abort(404);
        }
    }

    private function getGuard($app)
    {
        return match ($app) {
            'staff' => 'staff',
            'portal' => 'web',
            'admin' => 'admin',
        };
    }
}
