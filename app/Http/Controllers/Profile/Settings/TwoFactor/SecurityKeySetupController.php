<?php

namespace App\Http\Controllers\Profile\Settings\TwoFactor;

use App\Enums\TwoFactorTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\SecurityKeyDestroyRequest;
use App\Http\Requests\SecurityKeyStoreRequest;
use App\Services\BackupCodeService;
use App\Services\WebAuthnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SecurityKeySetupController extends Controller
{
    public function __construct(private WebAuthnService $webAuthnService) {}

    public function index(): Response
    {
        $securityKeys = auth()->user()->twoFactors()
            ->where('type', TwoFactorTypeEnum::SECURITY_KEY)
            ->get(['id', 'name', 'last_used_at'])
            ->map(fn ($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'last_used_at' => $key->last_used_at?->diffForHumans(),
            ]);

        return Inertia::render('Settings/Security/SecurityKeys', [
            'securityKeys' => $securityKeys,
        ]);
    }

    public function createOptions(): JsonResponse
    {
        $options = $this->webAuthnService->generateRegistrationOptions(
            auth()->user(),
            TwoFactorTypeEnum::SECURITY_KEY,
        );

        return response()->json($options);
    }

    public function store(SecurityKeyStoreRequest $request): RedirectResponse
    {
        $limitKey = 'security-key-setup-' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($limitKey, 10)) {
            throw ValidationException::withMessages(['credential' => 'Too many attempts. Please try again later.']);
        }
        RateLimiter::hit($limitKey, 120);

        try {
            $this->webAuthnService->verifyRegistration(
                $request->user(),
                $request->input('credential'),
                TwoFactorTypeEnum::SECURITY_KEY,
                $request->input('name'),
            );
        } catch (\RuntimeException $e) {
            Log::warning('Security key registration failed', ['user' => $request->user()->id, 'error' => $e->getMessage()]);
            throw ValidationException::withMessages(['credential' => 'Security key verification failed. Please try again.']);
        }

        // Auto-generate backup codes if none exist
        $backupCodeService = new BackupCodeService();
        if (! $backupCodeService->hasBackupCodes($request->user())) {
            $plaintextCodes = $backupCodeService->generate();
            $backupCodeService->storeForUser($request->user(), $plaintextCodes);
            session()->flash('backup_codes', $plaintextCodes);

            return redirect()->route('settings.security.backup-codes');
        }

        return redirect()->route('settings.security.security-keys');
    }

    public function destroy(SecurityKeyDestroyRequest $request): RedirectResponse
    {
        if (! Hash::check($request->input('password'), auth()->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Invalid password']);
        }

        auth()->user()->twoFactors()
            ->where('id', $request->input('keyId'))
            ->where('type', TwoFactorTypeEnum::SECURITY_KEY)
            ->delete();

        auth()->user()->deleteBackupCodesIfOrphaned();

        return redirect()->route('settings.security.security-keys');
    }
}
