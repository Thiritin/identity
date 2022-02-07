<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Hydra;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use JsonException;

class LoginController extends Controller
{
    public function view(Request $request)
    {
        if ($request->missing('login_challenge')) {
            return Redirect::route('auth.choose');
        }

        $hydra = new Hydra();
        $loginRequest = $hydra->getLoginRequest($request->get('login_challenge'));
        if ($loginRequest->skip === true) {
            return Redirect::to($this->acceptLogin($loginRequest->subject, $loginRequest->challenge, 0));
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
        return Inertia::location($this->acceptLogin(Auth::user()?->getHashId(),$request->get('login_challenge'),15552000));
    }

    /**
     * Accept OIDC Login Request
     *
     * @param string $login_challenge
     * @return Response
     * @throws JsonException
     */
    private function acceptLogin(string $subject, string $login_challenge, int $remember_seconds = 0): string
    {
        $hydra = new Hydra();
        $hydraResponse = $hydra->acceptLoginRequest($subject, $login_challenge, $remember_seconds);
        abort_if(empty($hydraResponse->redirect_to), 500);

        return $hydraResponse->redirect_to;
    }
}
