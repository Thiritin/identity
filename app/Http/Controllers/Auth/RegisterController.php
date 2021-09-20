<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request)
    {
        $user = User::create([
            "name" => $request->username,
            "email" => $request->email,
            "password" => Hash::make($request->password)
        ]);
        event(new Registered($user));
        Auth::login($user);
        return redirect()->route('dashboard');
    }
}
