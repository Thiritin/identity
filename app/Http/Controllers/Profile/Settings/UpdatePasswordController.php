<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UpdatePasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            "current_password" => [
                "required",
                "current_password",
            ],
            "password" => [
                "required",
                "confirmed",
                Password::min(8)->uncompromised()->mixedCase()->numbers(),
            ],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);
        return Inertia::render("Settings/UpdatePassword", ["success" => true]);
    }
}
