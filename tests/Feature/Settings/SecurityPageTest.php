<?php

use App\Enums\TwoFactorTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads the security page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.security'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security')
            ->has('totpEnabled')
            ->has('totpLastUsed')
            ->has('yubikeys')
        );
});

it('shows totp as enabled when user has totp', function () {
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

it('lists yubikeys for the user', function () {
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
            ->has('yubikeys', 1)
        );
});

it('requires authentication', function () {
    $this->get(route('settings.security'))
        ->assertRedirect();
});
