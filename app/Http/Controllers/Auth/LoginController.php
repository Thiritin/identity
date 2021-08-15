<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\HydraServiceProvider;
use App\Services\Hydra;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function view(\Illuminate\Http\Request $request)
    {
        return Inertia::render('Auth/Login',[
            'canSeeLogin' => $request->exists('login_challenge'),
        ]);
    }

    public function submit(LoginRequest $request)
    {
        $hydra = new Hydra();
        $loginData = [
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ];
        if (!Auth::once($loginData)) {
            throw ValidationException::withMessages(['nouser' => 'Wrong details']);
        }
        $hydraResponse = $hydra->acceptLoginRequest(Auth::user()->getHashId(), $request->get('login_challenge'));
        abort_if(empty($hydraResponse->redirect_to), 500);

        return Inertia::location($hydraResponse->redirect_to);
    }
}
