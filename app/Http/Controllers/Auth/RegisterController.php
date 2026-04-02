<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
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
        $user = User::create([
            'name' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        event(new Registered($user));
        Session::put('justRegisteredSkipLogin.user_id', $user->id);
        Session::forget('auth.email_flow');

        return redirect()->route('login.apps.redirect', ['app' => 'portal']);
    }
}
