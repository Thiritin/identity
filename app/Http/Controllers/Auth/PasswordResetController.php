<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Password;
use Str;

class PasswordResetController extends Controller
{
    public function view(Request $request)
    {
        return Inertia::render('Auth/ResetPassword', $request->only(['email', 'token']));
    }

    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                \Illuminate\Validation\Rules\Password::min(8)->uncompromised()->mixedCase()->numbers()
            ],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('auth.login.view')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
