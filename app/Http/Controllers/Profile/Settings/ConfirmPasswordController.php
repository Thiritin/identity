<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfirmPasswordController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $request->session()->passwordConfirmed();

        return Inertia::back();
    }
}
