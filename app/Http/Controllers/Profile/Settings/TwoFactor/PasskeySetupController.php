<?php

namespace App\Http\Controllers\Profile\Settings\TwoFactor;

use App\Enums\TwoFactorTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasskeyDestroyRequest;
use App\Http\Requests\PasskeyStoreRequest;
use App\Services\WebAuthnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasskeySetupController extends Controller
{
    public function __construct(private WebAuthnService $webAuthnService) {}

    public function index(): Response
    {
        $passkeys = auth()->user()->twoFactors()
            ->where('type', TwoFactorTypeEnum::PASSKEY)
            ->get(['id', 'name', 'last_used_at'])
            ->map(fn ($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'last_used_at' => $key->last_used_at?->diffForHumans(),
            ]);

        return Inertia::render('Settings/Security/Passkeys', [
            'passkeys' => $passkeys,
        ]);
    }

    public function createOptions(): JsonResponse
    {
        $options = $this->webAuthnService->generateRegistrationOptions(
            auth()->user(),
            TwoFactorTypeEnum::PASSKEY,
        );

        return response()->json($options);
    }

    public function store(PasskeyStoreRequest $request): RedirectResponse
    {
        $limitKey = 'passkey-setup-' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($limitKey, 10)) {
            throw ValidationException::withMessages(['credential' => 'Too many attempts. Please try again later.']);
        }
        RateLimiter::hit($limitKey, 120);

        try {
            $this->webAuthnService->verifyRegistration(
                $request->user(),
                $request->input('credential'),
                TwoFactorTypeEnum::PASSKEY,
                $request->input('name'),
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['credential' => $e->getMessage()]);
        }

        // Passkeys do NOT trigger backup code generation (they are not 2FA)
        return redirect()->route('settings.security.passkeys');
    }

    public function destroy(PasskeyDestroyRequest $request): RedirectResponse
    {
        if (! Hash::check($request->input('password'), auth()->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Invalid password']);
        }

        auth()->user()->twoFactors()
            ->where('id', $request->input('keyId'))
            ->where('type', TwoFactorTypeEnum::PASSKEY)
            ->delete();

        return redirect()->route('settings.security.passkeys');
    }
}
