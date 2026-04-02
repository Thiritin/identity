<?php

namespace App\Http\Controllers\Profile;

use App\Enums\TwoFactorTypeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SecurityController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totp = $user->twoFactors()->whereType(TwoFactorTypeEnum::TOTP)->first();
        $yubikeyCount = $user->twoFactors()
            ->where('type', TwoFactorTypeEnum::YUBIKEY)
            ->count();

        return Inertia::render('Settings/Security', [
            'totpEnabled' => $totp !== null,
            'totpLastUsed' => $totp?->last_used_at?->diffForHumans(),
            'yubikeyCount' => $yubikeyCount,
            'passwordChangedAt' => $user->password_changed_at?->diffForHumans(),
        ]);
    }

    public function password()
    {
        $user = Auth::user();

        return Inertia::render('Settings/Security/Password', [
            'passwordChangedAt' => $user->password_changed_at?->diffForHumans(),
        ]);
    }

    public function totp()
    {
        $user = Auth::user();
        $totp = $user->twoFactors()->whereType(TwoFactorTypeEnum::TOTP)->first();

        return Inertia::render('Settings/Security/Totp', [
            'totpEnabled' => $totp !== null,
            'totpLastUsed' => $totp?->last_used_at?->diffForHumans(),
        ]);
    }

    public function yubikey()
    {
        $user = Auth::user();
        $yubikeys = $user->twoFactors()
            ->where('type', TwoFactorTypeEnum::YUBIKEY)
            ->get(['id', 'name', 'last_used_at'])
            ->map(fn ($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'last_used_at' => $key->last_used_at?->diffForHumans(),
            ]);

        return Inertia::render('Settings/Security/Yubikey', [
            'yubikeys' => $yubikeys,
        ]);
    }
}
