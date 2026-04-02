<?php

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('User login success', function () {
    $password = Str::random(15);
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    Http::fake([
        '*' => Http::response([
            'redirect_to' => 'https://success.com',
        ]),
    ]);
    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
    ])->post(route('auth.login.password.submit'), [
        'remember' => true,
        'email' => $user->email,
        'password' => $password,
    ]);

    $response->assertStatus(302);
});

test('User false password error', function () {
    $password = Str::random(15);
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
    ])->postJson(route('auth.login.password.submit'), [
        'remember' => true,
        'email' => $user->email,
        'password' => 'wrong password',
    ]);

    $response->assertJsonValidationErrorFor('nouser');
});

test('User false email error', function () {
    $password = Str::random(15);
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
    ])->postJson(route('auth.login.password.submit'), [
        'remember' => true,
        'email' => 'falsemail@test.de',
        'password' => $password,
    ]);

    $response->assertJsonValidationErrorFor('nouser');
});

test('User false email formatting error', function () {
    $password = Str::random(15);

    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    $response = postJson(route('auth.login.password.submit'), [
        'remember' => true,
        'email' => 'falsemai2121ltest.de',
        'password' => $password,
    ]);

    $response->assertJsonValidationErrorFor('email');
});

test('login does not require POW with fewer than 3 failures', function () {
    $password = Str::random(15);
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    Http::fake([
        '*' => Http::response([
            'redirect_to' => 'https://success.com',
        ]),
    ]);

    // Fail twice with wrong password
    for ($i = 0; $i < 2; $i++) {
        $this->withSession([
            'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
        ])->postJson(route('auth.login.password.submit'), [
            'remember' => false,
            'email' => $user->email,
            'password' => 'wrong password',
        ])->assertJsonValidationErrorFor('nouser');
    }

    // Succeed with correct password, no altcha needed
    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
    ])->post(route('auth.login.password.submit'), [
        'remember' => false,
        'email' => $user->email,
        'password' => $password,
    ]);

    $response->assertStatus(302);
});

test('login requires POW after 3 failed attempts from same IP', function () {
    $password = Str::random(15);
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    // Fail 3 times
    for ($i = 0; $i < 3; $i++) {
        $this->withSession([
            'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
        ])->postJson(route('auth.login.password.submit'), [
            'remember' => false,
            'email' => $user->email,
            'password' => 'wrong password',
        ])->assertJsonValidationErrorFor('nouser');
    }

    Http::fake([
        '*' => Http::response([
            'redirect_to' => 'https://success.com',
        ]),
    ]);

    // Attempt with correct password but no altcha
    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
    ])->postJson(route('auth.login.password.submit'), [
        'remember' => false,
        'email' => $user->email,
        'password' => $password,
    ]);

    $response->assertJsonValidationErrorFor('altcha');
});

test('login succeeds with valid POW after 3 failures', function () {
    $password = Str::random(15);
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    // Fail 3 times
    for ($i = 0; $i < 3; $i++) {
        $this->withSession([
            'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
        ])->postJson(route('auth.login.password.submit'), [
            'remember' => false,
            'email' => $user->email,
            'password' => 'wrong password',
        ])->assertJsonValidationErrorFor('nouser');
    }

    Http::fake([
        '*' => Http::response([
            'redirect_to' => 'https://success.com',
        ]),
    ]);

    // Succeed with correct password and valid altcha bypass
    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
    ])->post(route('auth.login.password.submit'), [
        'remember' => false,
        'email' => $user->email,
        'password' => $password,
        'altcha' => config('altcha.testing_bypass'),
    ]);

    $response->assertStatus(302);
});

test('successful login clears POW requirement', function () {
    $password = Str::random(15);
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    // Create an App record so checkEmailVerification can find it
    $app = new App();
    $app->client_id = 'test-client';
    $app->user_id = $user->id;
    $app->saveQuietly();

    // Fail 3 times
    for ($i = 0; $i < 3; $i++) {
        $this->withSession([
            'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
        ])->postJson(route('auth.login.password.submit'), [
            'remember' => false,
            'email' => $user->email,
            'password' => 'wrong password',
        ])->assertJsonValidationErrorFor('nouser');
    }

    // Fake Hydra: getLoginRequest returns a proper login request, acceptLoginRequest returns redirect_to
    Http::fake([
        '*/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'https://success.com',
        ]),
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test',
            'skip' => false,
            'subject' => '',
            'client' => ['client_id' => 'test-client'],
        ]),
    ]);

    // Login with valid POW
    $this->withSession([
        'auth.login_challenge' => ['challenge' => 'test', 'client_id' => 'test-client'],
    ])->post(route('auth.login.password.submit'), [
        'remember' => false,
        'email' => $user->email,
        'password' => $password,
        'altcha' => config('altcha.testing_bypass'),
    ])->assertStatus(302);

    // Verify POW requirement is cleared
    expect(RateLimiter::tooManyAttempts('login-pow:127.0.0.1', 3))->toBeFalse();
});
