<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
