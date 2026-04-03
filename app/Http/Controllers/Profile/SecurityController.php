<?php

namespace App\Http\Controllers\Profile;

use App\Enums\TwoFactorTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\OauthSession;
use App\Services\BackupCodeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class SecurityController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $backupCodeService = new BackupCodeService();

        $totp = $user->twoFactors()->whereType(TwoFactorTypeEnum::TOTP)->first();
        $yubikeyCount = $user->twoFactors()
            ->where('type', TwoFactorTypeEnum::YUBIKEY)
            ->count();
        $passkeyCount = $user->twoFactors()
            ->where('type', TwoFactorTypeEnum::PASSKEY)
            ->count();
        $securityKeyCount = $user->twoFactors()
            ->where('type', TwoFactorTypeEnum::SECURITY_KEY)
            ->count();

        return Inertia::render('Settings/Security', [
            'totpEnabled' => $totp !== null,
            'totpLastUsed' => $totp?->last_used_at?->diffForHumans(),
            'yubikeyCount' => $yubikeyCount,
            'passkeyCount' => $passkeyCount,
            'securityKeyCount' => $securityKeyCount,
            'passwordChangedAt' => $user->password_changed_at?->diffForHumans(),
            'backupCodesEnabled' => $backupCodeService->hasBackupCodes($user),
            'backupCodesCount' => $backupCodeService->remainingCount($user),
            'sessionCount' => $user->oauthSessions()->count(),
        ]);
    }

    public function password()
    {
        $user = Auth::user();

        return Inertia::render('Settings/Security/Password', [
            'passwordChangedAt' => $user->password_changed_at?->diffForHumans(),
        ]);
    }

    public function email()
    {
        return Inertia::render('Settings/Security/Email', [
            'currentEmail' => Auth::user()->email,
        ]);
    }

    public function totp()
    {
        $user = Auth::user();
        $totp = $user->twoFactors()->whereType(TwoFactorTypeEnum::TOTP)->first();

        return Inertia::render('Settings/Security/Totp', [
            'totpEnabled' => $totp !== null,
            'totpLastUsed' => $totp?->last_used_at?->diffForHumans(),
            'enforced' => $totp === null && $user->isStaff(),
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

    public function backupCodes()
    {
        $user = Auth::user();
        $backupCodeService = new BackupCodeService();

        return Inertia::render('Settings/Security/BackupCodes', [
            'remainingCount' => $backupCodeService->remainingCount($user),
            'hasBackupCodes' => $backupCodeService->hasBackupCodes($user),
            'backupCodes' => session('backup_codes'),
        ]);
    }

    public function sessions()
    {
        $user = Auth::user();

        $appNames = App::whereNotNull('client_id')
            ->pluck('name', 'client_id');

        $sessions = $user->oauthSessions()
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (OauthSession $session) => [
                'id' => $session->id,
                'session_id' => $session->session_id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'app_names' => collect($session->client_ids ?? [])
                    ->map(fn ($id) => $appNames[$id] ?? $id)
                    ->values()
                    ->all(),
                'authenticated_at' => $session->authenticated_at?->diffForHumans(),
                'last_seen_at' => $session->last_seen_at?->diffForHumans(),
            ]);

        return Inertia::render('Settings/Security/Sessions', [
            'sessions' => $sessions,
            'currentSessionId' => Session::get('hydra_session_id'),
        ]);
    }
}
