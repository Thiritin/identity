<?php

namespace App\Http\Controllers\Auth;

use App\Events\AppLoginEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Hydra\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use JsonException;

class LoginController extends Controller
{
    private Request $request;

    public function view(Request $request)
    {
        $this->request = $request;
        if ($request->missing('login_challenge')) {
            return Redirect::route('auth.choose');
        }

        $hydra = new Client();
        $loginRequest = $hydra->getLoginRequest($request->get('login_challenge'));

        // redirect_to key is added when login request expired.
        if (isset($loginRequest['redirect_to'])) {
            return Redirect::to($loginRequest['redirect_to']);
        }

        /**
         * If skip is true do not show UI but simply accept
         */
        if ($loginRequest["skip"] === true) {
            return Redirect::to($this->acceptLogin($loginRequest['subject'], $loginRequest["challenge"], 0, $loginRequest));
        }

        return Inertia::render('Auth/Login');
    }

    public function submit(LoginRequest $request)
    {
        $this->request = $request;
        $loginData = [
            'email'    => $request->get('email'),
            'password' => $request->get('password'),
        ];
        if (Auth::attempt($loginData)) {
            return Inertia::location($this->acceptLogin(Auth::user()?->hashId(), $request->get('login_challenge'), 15552000));
        }
        throw ValidationException::withMessages(['nouser' => 'Wrong details']);
    }

    /**
     * Accept OIDC Login Request
     *
     * @param string $login_challenge
     * @return Response
     * @throws JsonException
     */
    private function acceptLogin(string $subject, string $login_challenge, int $remember_seconds = 0, array|null $loginRequest = null): string
    {
        $hydra = new Client();
        // If $loginRequest is not supplied, refetch.
        if ($loginRequest === null) {
            $loginRequest = $hydra->getLoginRequest($login_challenge);
        }

        // redirect_to key is added when login request expired.
        if (isset($loginRequest['redirect_to'])) {
            return Redirect::to($loginRequest['redirect_to']);
        }

        // Accept Login
        $hydraResponse = $hydra->acceptLoginRequest($subject, $login_challenge, $remember_seconds);

        // Send App Login Event
        event(new AppLoginEvent($loginRequest['client']['client_id'], $subject));

        // Throw error on missing redirect
        if (!isset($hydraResponse["redirect_to"])) {
            throw ValidationException::withMessages(['general' => $hydraResponse['error_description'] ?? "Unknown error"]);
        }

        // Return redirect url given in response
        return $hydraResponse["redirect_to"];
    }
}
