<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\TelegramNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UpdatePasswordController extends Controller
{
    public function __invoke(UpdatePasswordRequest $request, TelegramNotifier $notifier): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->update([
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ]);

        $notifier->notifyPasswordChanged($request->user());

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans('password_updated'),
        ])->back();
    }
}
