<?php

use App\Models\TwoFactor;
use App\Models\User;
use App\Services\BackupCodeService;

beforeEach(function () {
    $this->user = User::factory()->create(['password' => bcrypt('password')]);
    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()]);
});

it('shows backup codes row on security page when 2FA is enabled', function () {
    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    $this->get(route('settings.security'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security')
            ->where('backupCodesEnabled', true)
            ->where('backupCodesCount', 8)
        );
});

it('does not show backup codes row when no 2FA is enabled', function () {
    $this->get(route('settings.security'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security')
            ->where('backupCodesEnabled', false)
            ->where('backupCodesCount', 0)
        );
});

it('shows backup codes settings page with remaining count', function () {
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    $this->get(route('settings.security.backup-codes'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security/BackupCodes')
            ->where('remainingCount', 8)
            ->where('hasBackupCodes', true)
        );
});

it('regenerates backup codes with correct password', function () {
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    $this->post(route('settings.two-factor.backup-codes.regenerate'), [
        'password' => 'password',
    ])->assertRedirect(route('settings.security.backup-codes'));

    // Old codes should no longer work
    expect($service->verify($this->user, $codes[0]))->toBeFalse();

    // New codes should exist
    expect($service->remainingCount($this->user))->toBe(8);
});

it('rejects regeneration with wrong password', function () {
    $this->post(route('settings.two-factor.backup-codes.regenerate'), [
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('password');
});

it('rejects regeneration without password', function () {
    $this->post(route('settings.two-factor.backup-codes.regenerate'), [])
        ->assertSessionHasErrors('password');
});
