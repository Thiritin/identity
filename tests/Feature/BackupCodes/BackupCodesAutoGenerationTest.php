<?php

use App\Enums\TwoFactorTypeEnum;
use App\Models\TwoFactor;
use App\Models\User;
use App\Services\BackupCodeService;
use Illuminate\Support\Facades\Cache;
use RobThree\Auth\TwoFactorAuth;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('auto-generates backup codes when enabling TOTP for the first time', function () {
    // Set up cached TOTP secret
    $secret = (new TwoFactorAuth())->createSecret();
    $code = (new TwoFactorAuth())->getCode($secret);

    Cache::put(
        'totp-setup-' . $this->user->id . '-' . md5($this->user->email),
        ['secret' => $secret, 'qrCode' => 'data:image/png;base64,...']
    );

    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->post(route('settings.two-factor.totp.store'), [
            'code' => $code,
            'secret' => $secret,
        ])
        ->assertRedirect(route('settings.security.backup-codes'));

    $service = new BackupCodeService();
    expect($service->hasBackupCodes($this->user))->toBeTrue()
        ->and($service->remainingCount($this->user))->toBe(8);
});

it('does not regenerate backup codes when adding a second 2FA method', function () {
    // Create existing TOTP and backup codes
    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);
    $service = new BackupCodeService();
    $existingCodes = $service->generate();
    $service->storeForUser($this->user, $existingCodes);

    // The yubikey store requires a real Yubico call, so we test the TOTP path
    // by enabling TOTP again (simulate the second method scenario)
    // Since backup codes already exist, it should redirect to totp not backup-codes
    $secret = (new TwoFactorAuth())->createSecret();
    $code = (new TwoFactorAuth())->getCode($secret);

    // First remove existing TOTP to re-enable
    $this->user->twoFactors()->where('type', TwoFactorTypeEnum::TOTP)->forceDelete();

    Cache::put(
        'totp-setup-' . $this->user->id . '-' . md5($this->user->email),
        ['secret' => $secret, 'qrCode' => 'data:image/png;base64,...']
    );

    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->post(route('settings.two-factor.totp.store'), [
            'code' => $code,
            'secret' => $secret,
        ])
        ->assertRedirect(route('settings.security.totp'));

    // Should still have 8 codes (the original set, not regenerated)
    expect($service->remainingCount($this->user))->toBe(8);
    // Original codes should still work
    expect($service->verify($this->user, $existingCodes[0]))->toBeTrue();
});

it('deletes backup codes when last 2FA method is disabled', function () {
    $this->user->update(['password' => bcrypt('password')]);

    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('settings.two-factor.totp.destroy'), [
            'password' => 'password',
        ])
        ->assertRedirect(route('settings.security.totp'));

    expect($service->hasBackupCodes($this->user))->toBeFalse();
});

it('keeps backup codes when disabling one 2FA method but another remains', function () {
    $this->user->update(['password' => bcrypt('password')]);

    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);
    TwoFactor::factory()->yubikey()->create(['user_id' => $this->user->id]);
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('settings.two-factor.totp.destroy'), [
            'password' => 'password',
        ])
        ->assertRedirect(route('settings.security.totp'));

    expect($service->hasBackupCodes($this->user))->toBeTrue()
        ->and($service->remainingCount($this->user))->toBe(8);
});
