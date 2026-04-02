<?php

use App\Enums\TwoFactorTypeEnum;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('shows security key as available 2FA method', function () {
    $user = User::factory()->create();
    TwoFactor::factory()->securityKey()->for($user)->create();

    $url = URL::signedRoute('auth.two-factor', [
        'login_challenge' => 'test-challenge',
        'user' => $user->hashid,
    ], now()->addMinutes(30));

    $this->get($url)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/TwoFactor')
            ->where('lastUsedMethod', TwoFactorTypeEnum::SECURITY_KEY));
});

it('returns security key authentication options', function () {
    $user = User::factory()->create();
    TwoFactor::factory()->securityKey()->for($user)->create();

    $url = URL::signedRoute('auth.two-factor.security-key.options', [
        'login_challenge' => 'test-challenge',
        'user' => $user->hashid,
    ], now()->addMinutes(30));

    $this->getJson($url)
        ->assertSuccessful()
        ->assertJsonStructure(['challenge', 'rpId', 'allowCredentials']);
});

it('accepts security_key as valid 2FA method in validation', function () {
    $user = User::factory()->create();
    TwoFactor::factory()->securityKey()->for($user)->create();

    $url = URL::signedRoute('auth.two-factor.submit', [
        'login_challenge' => 'test-challenge',
        'user' => $user->hashid,
    ], now()->addMinutes(30));

    // Should fail on credential verification, NOT on method validation
    $response = $this->postJson($url, [
        'login_challenge' => 'test-challenge',
        'user' => $user->hashid,
        'method' => 'security_key',
        'credential' => '{}',
    ]);

    // The key assertion: error should NOT be on 'method' field
    $response->assertJsonMissingValidationErrors('method');
});
