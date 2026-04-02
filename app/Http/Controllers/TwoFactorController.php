<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BackupCodeService;
use App\Services\Hydra\Client;
use App\Services\Hydra\HydraRequestException;
use App\Services\YubicoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RobThree\Auth\TwoFactorAuth;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $request->validate(['login_challenge' => 'required|string', 'user' => 'required|string']);
        $allTwoFactors = User::findByHashidOrFail($request->get('user'))->twoFactors()
            ->orderBy('last_used_at', 'desc')->get(['id', 'type', 'last_used_at']);

        $twoFactors = $allTwoFactors->where('type', '!=', 'backup_codes')->values();
        $hasBackupCodes = $allTwoFactors->contains('type', 'backup_codes');

        if ($twoFactors->count() === 0) {
            return redirect()->route('auth.error', ['error' => 'no_two_factor']);
        }
        // Get last used two factor method
        $lastUsed = $twoFactors->firstWhere('last_used_at', '!=', null)->type ?? $twoFactors->first()->type ?? null;

        return Inertia::render('Auth/TwoFactor', [
            'twoFactors' => $twoFactors,
            'lastUsedMethod' => $lastUsed,
            'hasBackupCodes' => $hasBackupCodes,
            'submitFormUrl' => URL::signedRoute('auth.two-factor.submit',
                [
                    'login_challenge' => $request->get('login_challenge'),
                    'user' => $request->get('user'),
                    'remember' => $request->get('remember'),
                ],
                now()->addMinutes(30)),
        ]);
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'login_challenge' => 'required|string',
            'user' => 'required|string',
            'code' => 'required|string',
            'method' => 'required|string|in:yubikey,totp,backup_code',
            'remember' => 'nullable|boolean',
        ]);
        $user = User::findByHashidOrFail($request->get('user'));

        if ($user->isSuspended()) {
            return Redirect::route('auth.error', [
                'error' => 'account_suspended',
                'error_description' => trans('account_suspended'),
            ]);
        }

        if ($data['method'] === 'backup_code') {
            $limitKey = 'backup-code-verify-' . $user->id;
            if (RateLimiter::tooManyAttempts($limitKey, 10)) {
                throw ValidationException::withMessages(['code' => 'Too many attempts. Please try again later.']);
            }
            RateLimiter::hit($limitKey, 120);

            $backupCodeService = new BackupCodeService();
            if (! $backupCodeService->verify($user, $data['code'])) {
                throw ValidationException::withMessages(['code' => 'Invalid backup code.']);
            }
            RateLimiter::clear($limitKey);
        } else {
            $twoFactors = $user->twoFactors()->where('type', $request->get('method'))->get();
            if ($twoFactors->count() === 0) {
                throw ValidationException::withMessages(['code' => 'Invalid two factor method.']);
            }

            if ($data['method'] === 'yubikey') {
                if (! $this->verifyYubikey($data, $twoFactors, $user)) {
                    throw ValidationException::withMessages(['code' => 'Invalid Yubikey code.']);
                }
            } elseif ($data['method'] === 'totp') {
                if (! $this->verifyTOTP($data, $twoFactors, $user)) {
                    throw ValidationException::withMessages(['code' => 'Invalid TOTP code.']);
                }
            } else {
                throw ValidationException::withMessages(['code' => 'Invalid two factor method.']);
            }
        }

        try {
            $url = (new Client())->acceptLogin($user->hashid, $request->get('login_challenge'),
                $request->get('remember') ? '2592000' : '3600');
        } catch (HydraRequestException $e) {
            Log::warning('Failed to accept login after 2FA', [
                'challenge' => $request->get('login_challenge'),
                'message' => $e->getMessage(),
            ]);

            return Redirect::route('auth.error', [
                'error' => 'login_failed',
                'error_description' => 'The login session has expired. Please try again.',
            ]);
        }

        return Inertia::location($url);
    }

    public function verifyYubikey($data, Collection $twoFactors, $user): bool
    {
        // Get the first 12 characters of the code
        $identifier = substr($data['code'], 0, 12);
        // Find the identifier in $collection
        $twoFactor = $twoFactors->firstWhere('identifier', $identifier);
        if ($twoFactor === null) {
            throw ValidationException::withMessages(['code' => 'This Yubikey is not known.']);
        }

        $limitKey = 'yubikey-setup-' . $user->id;
        // Rate limit this endpoint
        if (RateLimiter::tooManyAttempts($limitKey, 10)) {
            throw ValidationException::withMessages(['code' => 'Too many attempts. Please try again later.']);
        }
        RateLimiter::hit($limitKey, 120);
        $success = (new YubicoService())->verify($data['code']);
        if ($success) {
            // Update last used at
            $twoFactor->update(['last_used_at' => now()]);
        }

        return $success;
    }

    public function verifyTOTP($data, Collection $twoFactors, $user): bool
    {
        $limitKey = 'totp-verify-' . $user->id;
        if (RateLimiter::tooManyAttempts($limitKey, 10)) {
            throw ValidationException::withMessages(['code' => 'Too many attempts. Please try again later.']);
        }
        RateLimiter::hit($limitKey, 120);

        $factorModel = $twoFactors->first();
        $success = (new TwoFactorAuth())->verifyCode($factorModel->secret, $data['code']);
        if ($success) {
            RateLimiter::clear($limitKey);
            $factorModel->update(['last_used_at' => now()]);
        }

        return $success;
    }
}
