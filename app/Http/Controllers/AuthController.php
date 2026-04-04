<?php

namespace App\Http\Controllers;

use App\Models\OauthSession;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

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

        $provider = Socialite::driver('idp-' . $app);

        try {
            $userInfo = $provider->user();
        } catch (InvalidStateException $e) {
            return redirect()->route('login.apps.redirect', ['app' => $app]);
        } catch (ClientException $e) {
            Log::warning('OAuth token exchange failed', [
                'app' => $app,
                'status' => $e->getResponse()->getStatusCode(),
                'body' => $e->getResponse()->getBody()->getContents(),
            ]);

            return redirect()->route('auth.error', [
                'error' => 'login_failed',
                'error_description' => 'The login attempt failed. Please try again.',
            ]);
        }

        $user = User::where('hashid', $userInfo->id)->first();
        if ($user === null) {
            Log::error('User not found during login callback', [
                'hashid' => $userInfo->id,
                'app' => $app,
            ]);

            return redirect()->route('auth.error', [
                'error' => 'user_not_found',
                'error_description' => 'Your user account could not be found.',
            ]);
        }

        Auth::guard($this->getGuard($app))->loginUsingId($user->id);

        $sid = $provider->getSid();
        if ($sid) {
            DB::table('sessions')
                ->where('id', Session::getId())
                ->update(['hydra_sid' => $sid]);

            $oauthSession = OauthSession::where('session_id', $sid)->first();
            if ($oauthSession) {
                $oauthSession->addClientId(config('services.apps.' . $app . '.client_id'));
            }
        }

        $provider->putToken(
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

    private function checkApp($app): void
    {
        if (in_array($app, ['portal', 'admin']) === false) {
            Log::warning('Unknown app requested in auth flow', ['app' => $app]);
            abort(404, "Unknown application: {$app}");
        }
    }

    private function getGuard($app)
    {
        return match ($app) {
            'portal' => 'web',
            'admin' => 'admin',
        };
    }
}
