<?php

namespace App\Http\Controllers\Profile\Settings\TwoFactor;

use App\Enums\TwoFactorTypeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TwoFactorController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Settings/TwoFactor', [
            'twoFactorMethods' => [
                [
                    'name' => 'Authenticator App (TOTP)',
                    'description' => 'Use one time codes to sign in to your account.',
                    'url' => route('settings.two-factor.totp'),
                    'active' => Auth::user()->twoFactors()->whereType(TwoFactorTypeEnum::TOTP)->exists(),
                ],
                [
                    'name' => 'Yubikey',
                    'description' => 'Use a Yubikey device to sign in to your account.',
                    'url' => route('settings.two-factor.yubikey'),
                    'active' => Auth::user()->twoFactors()->whereType(TwoFactorTypeEnum::YUBIKEY)->exists(),
                ],
            ],
        ]);
    }
}
