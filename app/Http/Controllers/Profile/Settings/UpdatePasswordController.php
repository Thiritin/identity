<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UpdatePasswordController extends Controller
{
    public function __invoke(UpdatePasswordRequest $request)
    {
        $data = $request->validated();

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return Inertia::render('Settings/UpdatePassword', ['success' => true]);
    }
}
