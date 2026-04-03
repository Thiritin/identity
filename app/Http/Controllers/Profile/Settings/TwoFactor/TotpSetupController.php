<?php

namespace App\Http\Controllers\Profile\Settings\TwoFactor;

use App\Enums\TwoFactorTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\TwoFactor\TotpDestroyRequest;
use App\Http\Requests\TwoFactor\TotpStoreRequest;
use App\Services\BackupCodeService;
use Illuminate\Http\JsonResponse;
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
            $secret = Cache::remember('totp-setup-' . auth()->user()->id . '-' . md5(auth()->user()->email), now()->addMinutes(10),
                function () use ($tfa) {
                    $secret = $tfa->createSecret();

                    return [
                        'secret' => $secret,
                        'qrCode' => $tfa->getQRCodeImageAsDataUri(config('app.name') . '-' . auth()->user()->name, $secret),
                    ];
                });
        }

        return Inertia::render('Settings/TwoFactor/AuthenticatorApp', [
            'secret' => $secret['secret'] ?? null,
            'qrCode' => $secret['qrCode'] ?? null,
        ]);

    }

    public function setup(): JsonResponse
    {
        $tfa = $this->createTwoFactorAuth();

        $secret = Cache::remember('totp-setup-' . auth()->user()->id . '-' . md5(auth()->user()->email), now()->addMinutes(10),
            function () use ($tfa) {
                $secret = $tfa->createSecret();

                return [
                    'secret' => $secret,
                    'qrCode' => $tfa->getQRCodeImageAsDataUri(config('app.name') . '-' . auth()->user()->name, $secret),
                ];
            });

        return response()->json([
            'secret' => $secret['secret'],
            'qr_code' => $secret['qrCode'],
        ]);
    }

    // Add new totp device
    public function store(TotpStoreRequest $request)
    {
        $tfa = $this->createTwoFactorAuth();
        $data = $request->validated();
        // Verify that data->code is equal to cached value
        $cachedValue = Cache::get('totp-setup-' . auth()->user()->id . '-' . md5(auth()->user()->email));
        if (! isset($cachedValue['secret'])) {
            throw ValidationException::withMessages(['code' => 'Your secret expired, please try again.']);
        }
        if ($cachedValue['secret'] !== $data['secret']) {
            throw ValidationException::withMessages(['code' => 'Invalid code']);
        }
        // Verify that code is valid
        if (! $tfa->verifyCode($data['secret'], $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Invalid code']);
        }
        // Add totp device to user
        auth()->user()->twoFactors()->create([
            'type' => TwoFactorTypeEnum::TOTP,
            'secret' => $data['secret'],
        ]);
        // Clear cache
        Cache::forget('totp-setup-' . auth()->user()->id . '-' . md5(auth()->user()->email));

        // Auto-generate backup codes if none exist
        $backupCodeService = new BackupCodeService();
        if (! $backupCodeService->hasBackupCodes(auth()->user())) {
            $plaintextCodes = $backupCodeService->generate();
            $backupCodeService->storeForUser(auth()->user(), $plaintextCodes);
            session()->flash('backup_codes', $plaintextCodes);

            return redirect()->route('settings.security.backup-codes');
        }

        return redirect()->route('settings.security.totp');
    }

    // Delete totp device
    public function destroy(TotpDestroyRequest $request)
    {
        $data = $request->validated();
        // Verify that password is correct
        $userPassword = auth()->user()->password;
        if (! Hash::check($data['password'], $userPassword)) {
            throw ValidationException::withMessages(['password' => 'Invalid password']);
        }
        // Delete totp device
        auth()->user()->twoFactors()->whereType(TwoFactorTypeEnum::TOTP)->delete();

        // Delete backup codes if no other 2FA methods remain
        $remainingMethods = auth()->user()->twoFactors()
            ->whereIn('type', [TwoFactorTypeEnum::TOTP, TwoFactorTypeEnum::YUBIKEY, TwoFactorTypeEnum::SECURITY_KEY])
            ->count();
        if ($remainingMethods === 0) {
            auth()->user()->twoFactors()->where('type', TwoFactorTypeEnum::BackupCodes)->delete();
        }

        return redirect()->route('settings.security.totp');

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
