<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Hydra\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class RememberSessionController extends Controller
{
    public function show()
    {
        $pendingLogin = Session::get('auth.pending_login');

        if (! $pendingLogin) {
            return Redirect::route('auth.login.view');
        }

        return Inertia::render('Auth/RememberSession');
    }

    public function submit(Request $request)
    {
        $request->validate([
            'remember' => 'required|boolean',
        ]);

        $pendingLogin = Session::get('auth.pending_login');

        if (! $pendingLogin) {
            return Redirect::route('auth.login.view');
        }

        try {
            $hydra = new Client();
            $loginRequest = $hydra->getLoginRequest($pendingLogin['login_challenge']);

            if (isset($loginRequest['redirect_to'])) {
                Session::forget('auth.pending_login');
                Session::forget('auth.email_flow');
                Session::forget('auth.login_challenge');

                return Redirect::route('auth.error', [
                    'error' => 'login_expired',
                    'error_description' => 'Your login session has expired. Please try again.',
                ]);
            }

            $remember = $request->boolean('remember') ? true : null;

            $url = $hydra->acceptLogin(
                $pendingLogin['user_hashid'],
                $pendingLogin['login_challenge'],
                $remember,
            );

            if (isset($pendingLogin['clear_pow_ip'])) {
                RateLimiter::clear('login-pow:' . $pendingLogin['clear_pow_ip']);
            }

            Session::forget('auth.pending_login');
            Session::forget('auth.email_flow');
            Session::forget('auth.login_challenge');

            return Inertia::location($url);
        } catch (\Exception $e) {
            Session::forget('auth.pending_login');
            Session::forget('auth.email_flow');
            Session::forget('auth.login_challenge');

            return Redirect::route('auth.error', [
                'error' => 'login_failed',
                'error_description' => 'Login could not be completed. Please try again.',
            ]);
        }
    }
}
