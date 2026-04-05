<?php

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unverified user can login to identity app', function () {
    $password = 'TestPassword123!';
    $user = User::factory()->create([
        'password' => Hash::make($password),
        'email_verified_at' => null,
    ]);

    App::withoutEvents(function () use ($user) {
        App::create([
            'client_id' => 'test-identity-client',
            'name' => 'Identity',
            'system_name' => 'identity',
            'user_id' => $user->id,
        ]);
    });

    Http::fake(function ($request) {
        if (str_contains($request->url(), '/requests/login/accept')) {
            return Http::response(['redirect_to' => 'http://localhost/consent']);
        }

        if (str_contains($request->url(), '/requests/login')) {
            return Http::response([
                'challenge' => 'test-challenge',
                'client' => ['client_id' => 'test-identity-client'],
                'request_url' => 'http://localhost',
                'requested_scope' => ['openid', 'email', 'profile'],
                'skip' => false,
                'subject' => '',
            ]);
        }

        return Http::response('', 200);
    });

    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test-challenge'],
    ])->post(route('auth.login.password.submit'), [
        'email' => $user->email,
        'password' => $password,
        'remember' => false,
    ]);

    // Should redirect to consent (login accepted)
    $response->assertRedirect();
});

test('unverified user cannot login to non-identity app', function () {
    $password = 'TestPassword123!';
    $user = User::factory()->create([
        'password' => Hash::make($password),
        'email_verified_at' => null,
    ]);

    App::withoutEvents(function () use ($user) {
        App::create([
            'client_id' => 'test-other-client',
            'name' => 'Other App',
            'system_name' => 'registration',
            'user_id' => $user->id,
        ]);
    });

    Http::fake(function ($request) {
        if (str_contains($request->url(), '/requests/login')) {
            return Http::response([
                'challenge' => 'test-challenge',
                'client' => ['client_id' => 'test-other-client'],
                'request_url' => 'http://localhost',
                'requested_scope' => ['openid', 'email', 'profile'],
                'skip' => false,
                'subject' => '',
            ]);
        }

        return Http::response('', 200);
    });

    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test-challenge'],
    ])->post(route('auth.login.password.submit'), [
        'email' => $user->email,
        'password' => $password,
        'remember' => false,
    ]);

    // Should redirect to the identity login flow
    $response->assertRedirect(route('login.redirect'));
});

test('verified user can login to any app', function () {
    $password = 'TestPassword123!';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    App::withoutEvents(function () use ($user) {
        App::create([
            'client_id' => 'test-other-client',
            'name' => 'Other App',
            'system_name' => 'registration',
            'user_id' => $user->id,
        ]);
    });

    Http::fake(function ($request) {
        if (str_contains($request->url(), '/requests/login/accept')) {
            return Http::response(['redirect_to' => 'http://localhost/consent']);
        }

        if (str_contains($request->url(), '/requests/login')) {
            return Http::response([
                'challenge' => 'test-challenge',
                'client' => ['client_id' => 'test-other-client'],
                'request_url' => 'http://localhost',
                'requested_scope' => ['openid', 'email', 'profile'],
                'skip' => false,
                'subject' => '',
            ]);
        }

        return Http::response('', 200);
    });

    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test-challenge'],
    ])->post(route('auth.login.password.submit'), [
        'email' => $user->email,
        'password' => $password,
        'remember' => false,
    ]);

    // Should redirect to consent (login accepted)
    $response->assertRedirect();
});
