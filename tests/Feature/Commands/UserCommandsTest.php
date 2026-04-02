<?php

use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// user:create

test('user:create creates a new user', function () {
    $this->artisan('user:create', ['name' => 'John', 'email' => 'john@example.com'])
        ->expectsQuestion('Password', 'secret123')
        ->expectsOutputToContain('created successfully')
        ->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'name' => 'John',
        'is_admin' => false,
    ]);
});

test('user:create with --admin flag creates an admin user', function () {
    $this->artisan('user:create', ['name' => 'Admin', 'email' => 'admin@example.com', '--admin' => true])
        ->expectsQuestion('Password', 'secret123')
        ->expectsOutputToContain('(admin)')
        ->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'email' => 'admin@example.com',
        'is_admin' => true,
    ]);
});

test('user:create fails if email already exists', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('user:create', ['name' => 'Dupe', 'email' => 'taken@example.com'])
        ->expectsOutputToContain('already exists')
        ->assertSuccessful();

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

// user:set-admin

test('user:set-admin grants admin access', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->artisan('user:set-admin', ['email' => $user->email])
        ->expectsOutputToContain('is now an admin')
        ->assertSuccessful();

    expect($user->fresh()->is_admin)->toBeTrue();
});

test('user:set-admin fails for nonexistent user', function () {
    $this->artisan('user:set-admin', ['email' => 'nobody@example.com'])
        ->expectsOutputToContain('User not found')
        ->assertSuccessful();
});

// user:remove-admin

test('user:remove-admin revokes admin access', function () {
    $user = User::factory()->admin()->create();

    $this->artisan('user:remove-admin', ['email' => $user->email])
        ->expectsOutputToContain('no longer an admin')
        ->assertSuccessful();

    expect($user->fresh()->is_admin)->toBeFalse();
});

test('user:remove-admin fails for nonexistent user', function () {
    $this->artisan('user:remove-admin', ['email' => 'nobody@example.com'])
        ->expectsOutputToContain('User not found')
        ->assertSuccessful();
});

// user:disable-2fa

test('user:disable-2fa removes two-factor auth', function () {
    $user = User::factory()->create();
    TwoFactor::create([
        'user_id' => $user->id,
        'type' => 'totp',
        'secret' => 'testsecret',
    ]);

    expect($user->twoFactors)->not->toBeNull();

    $this->artisan('user:disable-2fa', ['email' => $user->email])
        ->expectsOutputToContain('Two-factor authentication disabled')
        ->assertSuccessful();

    expect($user->fresh()->twoFactors)->toBeNull();
});

test('user:disable-2fa fails for nonexistent user', function () {
    $this->artisan('user:disable-2fa', ['email' => 'nobody@example.com'])
        ->expectsOutputToContain('User not found')
        ->assertSuccessful();
});
