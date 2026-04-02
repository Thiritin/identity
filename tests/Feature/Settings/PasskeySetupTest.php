<?php

use App\Enums\TwoFactorTypeEnum;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('shows passkeys settings page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.security.passkeys'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Settings/Security/Passkeys'));
});

it('shows registered passkeys', function () {
    $user = User::factory()->create();
    TwoFactor::factory()->passkey()->for($user)->create(['name' => 'MacBook']);
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.security.passkeys'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security/Passkeys')
            ->has('passkeys', 1)
            ->where('passkeys.0.name', 'MacBook'));
});

it('returns registration options', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('settings.two-factor.passkey.options'))
        ->assertSuccessful()
        ->assertJsonStructure(['challenge', 'rp', 'user', 'pubKeyCredParams']);
});

it('deletes a passkey with valid password', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $passkey = TwoFactor::factory()->passkey()->for($user)->create();
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('settings.two-factor.passkey.destroy'), [
            'password' => 'password',
            'keyId' => $passkey->id,
        ])
        ->assertRedirect(route('settings.security.passkeys'));
    expect($user->twoFactors()->where('type', TwoFactorTypeEnum::PASSKEY)->count())->toBe(0);
});

it('rejects delete with wrong password', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $passkey = TwoFactor::factory()->passkey()->for($user)->create();
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('settings.security.passkeys'))
        ->delete(route('settings.two-factor.passkey.destroy'), [
            'password' => 'wrong',
            'keyId' => $passkey->id,
        ]);
    expect($user->twoFactors()->where('type', TwoFactorTypeEnum::PASSKEY)->count())->toBe(1);
});

it('requires authentication', function () {
    $this->get('/settings/security/passkeys')->assertRedirect();
});
