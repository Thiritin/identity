<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Hydra\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
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

        /**
         * If skip is true do not show UI but simply accept
         */
        if ($loginRequest["skip"] === true) {
            return Redirect::to($hydra->acceptLogin($loginRequest['subject'], $loginRequest["challenge"], 0, $loginRequest));
        }

        return Inertia::render('Auth/Login');
    }

    public function submit(LoginRequest $request)
    {
        $loginData = [
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ];

        if (Auth::attempt($loginData)) {
            $user = Auth::user();
            if ($user->hasVerifiedEmail() === false) {
                Cache::put("web." . $user->id . ".loginChallenge", $request->get('login_challenge'), now()->addMinutes(10));
                return Redirect::route('verification.notice');
            }

            $url = (new Client())->acceptLogin($user->hashId(), $request->get('login_challenge'), 15552000);
            return Inertia::location($url);
        }

        throw ValidationException::withMessages(['nouser' => 'Wrong details']);
    }
}
