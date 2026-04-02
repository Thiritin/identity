<?php

use App\Enums\TwoFactorTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads the security menu page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.security'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security')
            ->has('totpEnabled')
            ->has('totpLastUsed')
            ->has('yubikeyCount')
            ->has('passwordChangedAt')
        );
});

it('shows totp as enabled on menu page', function () {
    $user = User::factory()->create();
    $user->twoFactors()->create([
        'type' => TwoFactorTypeEnum::TOTP,
        'secret' => 'test-secret',
        'last_used_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('settings.security'))
        ->assertInertia(fn ($page) => $page
            ->where('totpEnabled', true)
        );
});

it('shows yubikey count on menu page', function () {
    $user = User::factory()->create();
    $user->twoFactors()->create([
        'type' => TwoFactorTypeEnum::YUBIKEY,
        'name' => 'Work Key',
        'identifier' => 'abc123',
        'last_used_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('settings.security'))
        ->assertInertia(fn ($page) => $page
            ->where('yubikeyCount', 1)
        );
});

it('loads the password page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.security.password'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security/Password')
            ->has('passwordChangedAt')
        );
});

it('loads the email page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.security.email'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security/Email')
            ->has('currentEmail')
        );
});

it('loads the totp page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.security.totp'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security/Totp')
            ->has('totpEnabled')
            ->has('totpLastUsed')
        );
});

it('loads the yubikey page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.security.yubikey'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security/Yubikey')
            ->has('yubikeys')
        );
});

it('requires authentication for all security pages', function () {
    $this->get(route('settings.security'))->assertRedirect();
    $this->get(route('settings.security.password'))->assertRedirect();
    $this->get(route('settings.security.email'))->assertRedirect();
    $this->get(route('settings.security.totp'))->assertRedirect();
    $this->get(route('settings.security.yubikey'))->assertRedirect();
});

it('shows password_changed_at on menu page', function () {
    $user = User::factory()->create([
        'password_changed_at' => now()->subDays(3),
    ]);

    $this->actingAs($user)
        ->get(route('settings.security'))
        ->assertInertia(fn ($page) => $page
            ->where('passwordChangedAt', fn ($value) => str_contains($value, 'ago'))
        );
});
