<?php

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unverified user can login to portal app', function () {
    $password = 'TestPassword123!';
    $user = User::factory()->create([
        'password' => Hash::make($password),
        'email_verified_at' => null,
    ]);

    App::withoutEvents(function () use ($user) {
        App::create([
            'client_id' => 'test-portal-client',
            'name' => 'Portal',
            'system_name' => 'portal',
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
                'client' => ['client_id' => 'test-portal-client'],
                'request_url' => 'http://localhost',
                'requested_scope' => ['openid', 'email', 'profile'],
                'skip' => false,
                'subject' => '',
            ]);
        }

        return Http::response('', 200);
    });

    $response = $this->post(route('auth.login.password.submit'), [
        'login_challenge' => 'test-challenge',
        'email' => $user->email,
        'password' => $password,
        'remember' => false,
    ]);

    // Should redirect to consent (login accepted)
    $response->assertRedirect();
});

test('unverified user cannot login to non-portal app', function () {
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

    $response = $this->post(route('auth.login.password.submit'), [
        'login_challenge' => 'test-challenge',
        'email' => $user->email,
        'password' => $password,
        'remember' => false,
    ]);

    // Should redirect to portal login (not allowed to use non-portal app)
    $response->assertRedirect(route('login.apps.redirect', ['app' => 'portal']));
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

    $response = $this->post(route('auth.login.password.submit'), [
        'login_challenge' => 'test-challenge',
        'email' => $user->email,
        'password' => $password,
        'remember' => false,
    ]);

    // Should redirect to consent (login accepted)
    $response->assertRedirect();
});
