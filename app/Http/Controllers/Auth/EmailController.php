<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailCheckRequest;
use App\Models\User;
use App\Services\Hydra\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class EmailController extends Controller
{
    public function view(Request $request)
    {
        if ($request->has('login_challenge')) {
            $challenge = $request->get('login_challenge');

            try {
                $hydra = new Client();
                $loginRequest = $hydra->getLoginRequest($challenge);

                if (isset($loginRequest['redirect_to'])) {
                    return Redirect::to($loginRequest['redirect_to']);
                }

                Session::put('auth.login_challenge', [
                    'challenge' => $challenge,
                    'client_id' => $loginRequest['client']['client_id'],
                    'client_name' => $loginRequest['client']['client_name'] ?? null,
                    'skip' => $loginRequest['skip'] ?? false,
                    'subject' => $loginRequest['subject'] ?? '',
                ]);
            } catch (\Exception $e) {
                return Redirect::route('auth.error', [
                    'error' => 'invalid_challenge',
                    'error_description' => 'The login challenge is invalid or has expired.',
                ]);
            }

            if (($loginRequest['skip'] ?? false) === true) {
                $skipUser = User::findByHashid($loginRequest['subject'] ?? '');
                if ($skipUser) {
                    Session::put('auth.email_flow.email', $skipUser->email);
                }

                return Redirect::route('auth.login.password.view');
            }

            return Redirect::route('auth.login.view');
        }

        if (! Session::has('auth.login_challenge')) {
            return Redirect::route('login.redirect');
        }

        if (Session::get('auth.login_challenge.skip') === true) {
            if (! Session::has('auth.email_flow.email')) {
                $skipUser = User::findByHashid(Session::get('auth.login_challenge.subject', ''));
                if ($skipUser) {
                    Session::put('auth.email_flow.email', $skipUser->email);
                }
            }

            return Redirect::route('auth.login.password.view');
        }

        return Inertia::render('Auth/Email');
    }

    public function submit(EmailCheckRequest $request)
    {
        Session::put('auth.email_flow.email', $request->email);

        if (User::where('email', $request->email)->exists()) {
            if ($request->filled('password')) {
                Session::put('auth.email_flow.password', $request->password);
            }

            return Redirect::route('auth.login.password.view');
        }

        return Redirect::route('auth.register.view');
    }
}
