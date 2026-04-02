<?php

use App\Enums\TwoFactorTypeEnum;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('shows security keys settings page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.security.security-keys'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Settings/Security/SecurityKeys'));
});

it('shows registered security keys', function () {
    $user = User::factory()->create();
    TwoFactor::factory()->securityKey()->for($user)->create(['name' => 'YubiKey 5']);
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.security.security-keys'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security/SecurityKeys')
            ->has('securityKeys', 1)
            ->where('securityKeys.0.name', 'YubiKey 5'));
});

it('returns registration options', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('settings.two-factor.security-key.options'))
        ->assertSuccessful()
        ->assertJsonStructure(['challenge', 'rp', 'user', 'pubKeyCredParams']);
});

it('deletes a security key with valid password', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $securityKey = TwoFactor::factory()->securityKey()->for($user)->create();
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('settings.two-factor.security-key.destroy'), [
            'password' => 'password',
            'keyId' => $securityKey->id,
        ])
        ->assertRedirect(route('settings.security.security-keys'));
    expect($user->twoFactors()->where('type', TwoFactorTypeEnum::SECURITY_KEY)->count())->toBe(0);
});

it('rejects delete with wrong password', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $securityKey = TwoFactor::factory()->securityKey()->for($user)->create();
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('settings.security.security-keys'))
        ->delete(route('settings.two-factor.security-key.destroy'), [
            'password' => 'wrong',
            'keyId' => $securityKey->id,
        ]);
    expect($user->twoFactors()->where('type', TwoFactorTypeEnum::SECURITY_KEY)->count())->toBe(1);
});

it('deletes backup codes when last security key (last 2FA) is removed', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $securityKey = TwoFactor::factory()->securityKey()->for($user)->create();
    TwoFactor::factory()->backupCodes()->for($user)->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('settings.two-factor.security-key.destroy'), [
            'password' => 'password',
            'keyId' => $securityKey->id,
        ]);

    expect($user->twoFactors()->where('type', TwoFactorTypeEnum::BackupCodes)->count())->toBe(0);
});

it('keeps backup codes when other 2FA methods remain', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $securityKey = TwoFactor::factory()->securityKey()->for($user)->create();
    TwoFactor::factory()->totp()->for($user)->create();
    TwoFactor::factory()->backupCodes()->for($user)->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('settings.two-factor.security-key.destroy'), [
            'password' => 'password',
            'keyId' => $securityKey->id,
        ]);

    expect($user->twoFactors()->where('type', TwoFactorTypeEnum::BackupCodes)->count())->toBe(1);
});

it('requires authentication', function () {
    $this->get('/settings/security/security-keys')->assertRedirect();
});
