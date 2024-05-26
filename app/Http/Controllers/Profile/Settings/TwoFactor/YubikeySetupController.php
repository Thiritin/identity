<?php

namespace App\Http\Controllers\Profile\Settings\TwoFactor;

use App\Http\Controllers\Controller;
use App\Http\Requests\YubikeyDestroyRequest;
use App\Http\Requests\YubikeyStoreRequest;
use App\Services\YubicoService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class YubikeySetupController extends Controller
{
    // List all Yubikeys
    private YubicoService $yubicoService;

    public function __construct()
    {
        $this->yubicoService = new YubicoService();
    }

    public function index()
    {
        return Inertia::render('Settings/TwoFactor/YubikeySetup', [
            'keys' => auth()->user()->twoFactors()->where('type', 'yubikey')->get([
                'id', 'name', 'last_used_at'
            ]),
        ]);
    }

    // Add new Yubikey
    public function store(YubikeyStoreRequest $request)
    {
        $limitKey = 'yubikey-setup-'.$request->user()->id;
        // Rate limit this endpoint
        if (RateLimiter::tooManyAttempts($limitKey, 10)) {
            throw ValidationException::withMessages(['code' => 'Too many attempts. Please try again later.']);
        }
        RateLimiter::hit($limitKey, 120);
        $yubico = new YubicoService();
        $yubico->verify($request->input('code'));

        // Check if the Yubikey is already registered
        if ($request->user()->twoFactors()->where('identifier', $yubico->identifier)->exists()) {
            throw ValidationException::withMessages(['code' => 'This Yubikey is already registered.']);
        }
        // Create the Yubikey
        $request->user()->twoFactors()->create([
            'name' => $request->input('name'),
            'identifier' => $yubico->identifier,
            'type' => 'yubikey',
        ]);
        return redirect()->route('settings.two-factor.yubikey');
    }

    // Delete Yubikey
    public function destroy(YubikeyDestroyRequest $request)
    {
        $data = $request->validated();
        // Verify that password is correct
        $userPassword = auth()->user()->password;
        if (!Hash::check($data['password'], $userPassword)) {
            throw ValidationException::withMessages(['password' => 'Invalid password']);
        }
        // Delete totp device
        auth()->user()->twoFactors()->where('id', $request->input('keyId'))->delete();
        return redirect()->route('settings.two-factor');
    }

    // Thanks to Randall Wilk randall@randallwilk.dev, taken from rawilk/yubikey-u2f
    private function generateSignature(array $parameters): string
    {
        return $this->yubicoService->generateSignature($parameters);
    }

    // Thanks to Randall Wilk randall@randallwilk.dev, taken from rawilk/yubikey-u2f
    private function verifyResponseSignature(array $response): bool
    {
        return $this->yubicoService->verifyResponseSignature($response);
    }


    private function convertResponseToArray(string $response): array
    {
        return $this->yubicoService->convertResponseToArray($response);
    }
}
