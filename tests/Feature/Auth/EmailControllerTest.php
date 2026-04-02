<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('email page with valid login_challenge validates and redirects', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge',
            'client' => ['client_id' => 'test-client', 'client_name' => 'Test App'],
            'request_url' => 'http://localhost',
            'requested_scope' => ['openid'],
            'skip' => false,
            'subject' => '',
        ]),
    ]);

    $this->get(route('auth.login.view', ['login_challenge' => 'test-challenge']))
        ->assertRedirect(route('auth.login.view'));

    expect(session('auth.login_challenge.challenge'))->toBe('test-challenge');
    expect(session('auth.login_challenge.client_id'))->toBe('test-client');
});

test('email page renders when login_challenge in session', function () {
    $this->withSession([
        'auth.login_challenge' => [
            'challenge' => 'test-challenge',
            'client_id' => 'test-client',
            'client_name' => 'Test App',
            'skip' => false,
            'subject' => '',
        ],
    ])
        ->get(route('auth.login.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Email'));
});

test('email page without login_challenge redirects to portal', function () {
    $this->get(route('auth.login.view'))
        ->assertRedirect(route('login.apps.redirect', ['app' => 'portal']));
});

test('email submit with existing user redirects to password page', function () {
    $user = User::factory()->create();

    $this->withSession(['auth.login_challenge' => ['challenge' => 'test-challenge']])
        ->post(route('auth.login.submit'), ['email' => $user->email])
        ->assertRedirect(route('auth.login.password.view'));

    expect(session('auth.email_flow.email'))->toBe($user->email);
});

test('email submit with new email redirects to register page', function () {
    $this->withSession(['auth.login_challenge' => ['challenge' => 'test-challenge']])
        ->post(route('auth.login.submit'), ['email' => 'newuser@example.com'])
        ->assertRedirect(route('auth.register.view'));

    expect(session('auth.email_flow.email'))->toBe('newuser@example.com');
});

test('email submit validates email format', function () {
    $this->postJson(route('auth.login.submit'), ['email' => 'not-an-email'])
        ->assertJsonValidationErrorFor('email');
});

test('email submit requires email', function () {
    $this->postJson(route('auth.login.submit'), [])
        ->assertJsonValidationErrorFor('email');
});

test('password page loads with session data', function () {
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

    $this->withSession([
        'auth.email_flow.email' => 'test@example.com',
        'auth.login_challenge' => ['challenge' => 'test-challenge'],
    ])
        ->get(route('auth.login.password.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->has('email')
        );
});

test('password page without session redirects to login', function () {
    $this->get(route('auth.login.password.view'))
        ->assertRedirect(route('auth.login.view'));
});

test('register page loads with session data', function () {
    $this->withSession([
        'auth.email_flow.email' => 'newuser@example.com',
    ])
        ->get(route('auth.register.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('email')
        );
});

test('register page without session redirects to login', function () {
    $this->get(route('auth.register.view'))
        ->assertRedirect(route('auth.login.view'));
});
