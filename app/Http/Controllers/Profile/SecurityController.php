<?php

namespace App\Http\Controllers\Profile;

use App\Enums\TwoFactorTypeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SecurityController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $totp = $user->twoFactors()->whereType(TwoFactorTypeEnum::TOTP)->first();
        $yubikeys = $user->twoFactors()
            ->where('type', TwoFactorTypeEnum::YUBIKEY)
            ->get(['id', 'name', 'last_used_at']);

        return Inertia::render('Settings/Security', [
            'totpEnabled' => $totp !== null,
            'totpLastUsed' => $totp?->last_used_at?->diffForHumans(),
            'yubikeys' => $yubikeys,
        ]);
    }
}
