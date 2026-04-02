<?php

use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('shows hasPasskeys true when user has passkeys', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge',
            'client' => ['client_id' => 'test-client'],
            'request_url' => 'http://localhost',
            'requested_scope' => ['openid'],
            'skip' => false,
            'subject' => '',
        ]),
    ]);

    $user = User::factory()->create();
    TwoFactor::factory()->passkey()->for($user)->create();

    $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test-challenge', 'client_id' => 'test-client'],
        'auth.email_flow.email' => $user->email,
    ])
        ->get(route('auth.login.password.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Login')->where('hasPasskeys', true));
});

it('shows hasPasskeys false when user has no passkeys', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge',
            'client' => ['client_id' => 'test-client'],
            'request_url' => 'http://localhost',
            'requested_scope' => ['openid'],
            'skip' => false,
            'subject' => '',
        ]),
    ]);

    $user = User::factory()->create();

    $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test-challenge', 'client_id' => 'test-client'],
        'auth.email_flow.email' => $user->email,
    ])
        ->get(route('auth.login.password.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Login')->where('hasPasskeys', false));
});

it('returns passkey authentication options', function () {
    $user = User::factory()->create();
    TwoFactor::factory()->passkey()->for($user)->create();

    $this->withSession([
        'auth.email_flow.email' => $user->email,
        'auth.login_challenge' => ['challenge' => 'test-challenge', 'client_id' => 'test-client'],
    ])
        ->getJson(route('auth.login.passkey.options'))
        ->assertSuccessful()
        ->assertJsonStructure(['challenge', 'rpId', 'allowCredentials']);
});
