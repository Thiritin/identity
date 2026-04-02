<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordController extends Controller
{
    public function __invoke(UpdatePasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->update([
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ]);

        return redirect()->route('settings.security.password')->with('message', 'password_updated');
    }
}
