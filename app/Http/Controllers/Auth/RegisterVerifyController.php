<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyEmailCodeNotification;
use GrantHolle\Altcha\Rules\ValidAltcha;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class RegisterVerifyController extends Controller
{
    public function view()
    {
        if (! Session::has('auth.register')) {
            return Redirect::route('auth.register.view');
        }

        return Inertia::render('Auth/RegisterVerify');
    }

    public function submit(Request $request)
    {
        $request->validate([
            'altcha' => ['required', new ValidAltcha()],
        ]);

        $data = Session::get('auth.register');

        if (! $data) {
            return Redirect::route('auth.register.view');
        }

        $user = User::create([
            'name' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        Session::forget('auth.register');

        $code = substr(str_shuffle(str_repeat('ABCDEFGHJKMNPQRSTUVWXYZ23456789', 3)), 0, 6);
        Session::put('auth.verify_code', [
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new VerifyEmailCodeNotification($code));

        return Redirect::route('auth.register.code');
    }
}
