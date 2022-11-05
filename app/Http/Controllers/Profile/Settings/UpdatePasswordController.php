<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            "current_password" => [
                "required",
                "current_password"
            ],
            "password" => [
                "required",
                "confirmed",
                Password::min(8)->uncompromised()->mixedCase()->numbers()
            ],
            "destroy_sessions" => [
                "boolean"
            ]
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        if($data['destroy_sessions']) {
            Auth::logout();
        }

    }
}
