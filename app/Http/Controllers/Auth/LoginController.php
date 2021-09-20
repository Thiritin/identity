<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\HydraServiceProvider;
use App\Services\Hydra;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function view(\Illuminate\Http\Request $request)
    {
        if ($request->missing('login_challenge')) {
            if (Auth::check()) {
                return Redirect::route('dashboard');
            }
            return Redirect::route('auth.choose');
        }
        if (Auth::check()) {
            return redirect($this->acceptLogin($request->get('login_challenge')));
        }

        return Inertia::render('Auth/Login');
    }

    public function submit(LoginRequest $request)
    {
        $loginData = [
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ];
        if (!Auth::once($loginData)) {
            throw ValidationException::withMessages(['nouser' => 'Wrong details']);
        }
        return Inertia::location($this->acceptLogin($request->get('login_challenge')));
    }

    /**
     * Accept OIDC Login Request
     *
     * @param  string  $login_challenge
     * @return \Illuminate\Http\Response
     * @throws \JsonException
     */
    private function acceptLogin(string $login_challenge): string
    {
        $hydra = new Hydra();
        $hydraResponse = $hydra->acceptLoginRequest(Auth::user()->getHashId(), $login_challenge);
        abort_if(empty($hydraResponse->redirect_to), 500);

        return $hydraResponse->redirect_to;
    }
}
