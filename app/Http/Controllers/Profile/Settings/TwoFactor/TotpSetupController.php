<?php

namespace App\Http\Controllers\Profile\Settings\TwoFactor;

use App\Enums\TwoFactorTypeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

class TotpSetupController extends Controller
{
    // List active TOTP device
    public function show()
    {
        $tfa = $this->createTwoFactorAuth();
        // Determine if Two Factor is enabled for the person
        $twoFactorEnabled = auth()->user()->twoFactors()->whereType(TwoFactorTypeEnum::TOTP)->exists();
        if ($twoFactorEnabled === false) {
            $secret = Cache::remember('user-'.auth()->user()->id.'-two-factor-user-cache', now()->addHour(),
                function () use ($tfa) {
                    $secret = $tfa->createSecret();
                    return [
                        'secret' => $secret,
                        'qrCode' => $tfa->getQRCodeImageAsDataUri(config('app.name')."-".auth()->user()->name, $secret),
                    ];
                });
        }
        return Inertia::render('Settings/TwoFactor/AuthenticatorApp', [
            'secret' => $secret['secret'] ?? null,
            'qrCode' => $secret['qrCode'] ?? null,
        ]);

    }

    // Add new totp device
    public function store(Request $request)
    {
        $tfa = $this->createTwoFactorAuth();
        $data = $request->validate([
            'code' => 'required|numeric|digits:6',
            'secret' => 'required|string',
        ]);
        // Verify that data->code is equal to cached value
        $cachedValue = Cache::get('user-'.auth()->user()->id.'-two-factor-user-cache');
        if ($cachedValue['secret'] !== $data['secret']) {
            throw ValidationException::withMessages(['code' => 'Invalid code']);
        }
        // Verify that code is valid
        if (!$tfa->verifyCode($data['secret'], $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Invalid code']);
        }
        // Add totp device to user
        auth()->user()->twoFactors()->create([
            'type' => TwoFactorTypeEnum::TOTP,
            'secret' => $data['secret'],
        ]);
        // Clear cache
        Cache::forget('user-'.auth()->user()->id.'-two-factor-user-cache');
        return redirect()->route('settings.two-factor');
    }

    // Delete totp device
    public function destroy(Request $request)
    {
        $data = $request->validate([
            'password' => 'required|string',
        ]);
        // Verify that password is correct
        $userPassword = auth()->user()->password;
        if (!Hash::check($data['password'], $userPassword)) {
            throw ValidationException::withMessages(['password' => 'Invalid password']);
        }
        // Delete totp device
        auth()->user()->twoFactors()->whereType(TwoFactorTypeEnum::TOTP)->delete();
        return redirect()->route('settings.two-factor');

    }

    private function createTwoFactorAuth(): TwoFactorAuth
    {
        return new TwoFactorAuth(
            issuer: config('app.name'),
            digits: 6,
            qrcodeprovider: new EndroidQrCodeProvider(margin: 2)
        );
    }
}
