<?php

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('remember session page renders when pending login exists', function () {
    $this->withSession([
        'auth.pending_login' => [
            'user_hashid' => 'abc123',
            'login_challenge' => 'test-challenge',
        ],
    ])
        ->get(route('auth.remember-session'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/RememberSession'));
});

test('remember session page redirects to login when no pending login', function () {
    $this->get(route('auth.remember-session'))
        ->assertRedirect(route('auth.login.view'));
});

test('submitting remember yes calls hydra with remember true', function () {
    $user = User::factory()->create();
    $app = new App();
    $app->client_id = 'test-client';
    $app->user_id = $user->id;
    $app->saveQuietly();

    Http::fake([
        '*/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'https://success.com',
        ]),
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge',
            'skip' => false,
            'subject' => '',
            'client' => ['client_id' => 'test-client'],
        ]),
    ]);

    $response = $this->withSession([
        'auth.pending_login' => [
            'user_hashid' => $user->hashid,
            'login_challenge' => 'test-challenge',
        ],
    ])->post(route('auth.remember-session.submit'), [
        'remember' => true,
    ]);

    $response->assertRedirect('https://success.com');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'login/accept')) {
            return false;
        }

        return $request['remember'] === true && $request['remember_for'] === 0;
    });
});

test('submitting remember no calls hydra without remember fields', function () {
    $user = User::factory()->create();
    $app = new App();
    $app->client_id = 'test-client';
    $app->user_id = $user->id;
    $app->saveQuietly();

    Http::fake([
        '*/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'https://success.com',
        ]),
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge',
            'skip' => false,
            'subject' => '',
            'client' => ['client_id' => 'test-client'],
        ]),
    ]);

    $response = $this->withSession([
        'auth.pending_login' => [
            'user_hashid' => $user->hashid,
            'login_challenge' => 'test-challenge',
        ],
    ])->post(route('auth.remember-session.submit'), [
        'remember' => false,
    ]);

    $response->assertRedirect('https://success.com');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'login/accept')) {
            return false;
        }

        return ! isset($request['remember']);
    });
});

test('submit redirects to login when no pending login', function () {
    $this->post(route('auth.remember-session.submit'), [
        'remember' => true,
    ])->assertRedirect(route('auth.login.view'));
});

test('submit clears session data after success', function () {
    $user = User::factory()->create();
    $app = new App();
    $app->client_id = 'test-client';
    $app->user_id = $user->id;
    $app->saveQuietly();

    Http::fake([
        '*/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'https://success.com',
        ]),
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge',
            'skip' => false,
            'subject' => '',
            'client' => ['client_id' => 'test-client'],
        ]),
    ]);

    $this->withSession([
        'auth.pending_login' => [
            'user_hashid' => $user->hashid,
            'login_challenge' => 'test-challenge',
        ],
        'auth.email_flow' => ['email' => $user->email],
        'auth.login_challenge' => ['challenge' => 'test-challenge'],
    ])->post(route('auth.remember-session.submit'), [
        'remember' => true,
    ]);

    $this->assertNull(session('auth.pending_login'));
    $this->assertNull(session('auth.email_flow'));
    $this->assertNull(session('auth.login_challenge'));
});
