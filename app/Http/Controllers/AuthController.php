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
    public function login()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->intended(route('dashboard'));
        }

        $url = Socialite::driver('idp-identity')
            ->scopes(config('services.apps.identity.scopes'))
            ->redirect();

        return Inertia::location($url->getTargetUrl());
    }

    public function loginCallback()
    {
        $provider = Socialite::driver('idp-identity');

        try {
            $userInfo = $provider->user();
        } catch (InvalidStateException $e) {
            return redirect()->route('login.redirect');
        } catch (ClientException $e) {
            Log::warning('OAuth token exchange failed', [
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
            ]);

            return redirect()->route('auth.error', [
                'error' => 'user_not_found',
                'error_description' => 'Your user account could not be found.',
            ]);
        }

        Auth::guard('web')->loginUsingId($user->id);

        // Grace window for the sudo (password confirmation) modal — a user who
        // just logged in shouldn't be asked for their password again immediately.
        Session::put('auth.logged_in_at', now()->unix());

        $sid = $provider->getSid();
        if ($sid) {
            DB::table('sessions')
                ->where('id', Session::getId())
                ->update(['hydra_sid' => $sid]);

            $oauthSession = OauthSession::where('session_id', $sid)->first();
            if ($oauthSession) {
                $oauthSession->addClientId(config('services.apps.identity.client_id'));
            }
        }

        $provider->putToken(
            token: $userInfo->token,
            refreshToken: $userInfo->refreshToken,
            expiresIn: now()->addSeconds($userInfo->expiresIn),
        );

        return redirect()->intended(route('dashboard'));
    }

    public function logout()
    {
        return Socialite::driver('idp-identity')->logoutAll();
    }

    public function frontchannelLogout()
    {
        Auth::guard('web')->logout();
        Socialite::driver('idp-identity')->clearToken();
    }
}
