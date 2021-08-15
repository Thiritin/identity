<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request)
    {
        $user = User::create([
            "name" => $request->username,
            "email" => $request->email
        ]);
        event(new Registered($user));
        Auth::login($user);
        return redirect()->route('dashboard');
    }
}
