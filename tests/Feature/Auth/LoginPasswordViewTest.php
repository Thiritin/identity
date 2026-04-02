<?php

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('login password page loads with valid session', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge',
            'client' => ['client_id' => 'test-client', 'client_name' => 'Test'],
            'skip' => false,
            'subject' => '',
        ]),
    ]);

    $user = User::factory()->create();

    $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test-challenge', 'client_id' => 'test-client'],
        'auth.email_flow' => ['email' => $user->email],
    ])->get(route('auth.login.password.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

test('login password page redirects when session is missing', function () {
    $this->get(route('auth.login.password.view'))
        ->assertRedirect(route('auth.login.view'));
});

test('login password page auto logs in when hydra marks login as skippable', function () {
    $user = User::factory()->create();

    Http::fake([
        '*/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'https://app.example/callback',
        ]),
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge',
            'client' => ['client_id' => 'test-client', 'client_name' => 'Test'],
            'skip' => true,
            'subject' => $user->hashid,
        ]),
    ]);

    $app = new App();
    $app->client_id = 'test-client';
    $app->system_name = 'portal';
    $app->user_id = $user->id;
    $app->saveQuietly();

    $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test-challenge', 'client_id' => 'test-client'],
    ])->get(route('auth.login.password.view'))
        ->assertRedirect('https://app.example/callback');
});

test('login password page redirects when Hydra challenge is expired', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'error' => 'Not Found',
            'error_description' => 'Unable to locate the requested resource',
        ], 404),
    ]);

    $user = User::factory()->create();

    $this->withSession([
        'auth.login_challenge' => ['challenge' => 'expired-challenge', 'client_id' => 'test-client'],
        'auth.email_flow' => ['email' => $user->email],
    ])->get(route('auth.login.password.view'))
        ->assertRedirect(route('auth.login.view'));
});

test('login password page redirects when Hydra returns 410 Gone', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'error' => 'Gone',
            'error_description' => 'The login challenge has expired',
        ], 410),
    ]);

    $user = User::factory()->create();

    $this->withSession([
        'auth.login_challenge' => ['challenge' => 'gone-challenge', 'client_id' => 'test-client'],
        'auth.email_flow' => ['email' => $user->email],
    ])->get(route('auth.login.password.view'))
        ->assertRedirect(route('auth.login.view'));
});
