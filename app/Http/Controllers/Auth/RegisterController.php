<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function view()
    {
        $email = Session::get('auth.email_flow.email');

        if (! $email) {
            return Redirect::route('auth.login.view');
        }

        return Inertia::render('Auth/Register', [
            'email' => $email,
        ]);
    }

    public function __invoke(RegisterRequest $request)
    {
        Session::put('auth.register', [
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        return Redirect::route('auth.register.verify');
    }
}
