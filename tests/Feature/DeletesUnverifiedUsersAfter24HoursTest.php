<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('Deletes users after 24 Hours', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subHours(25),
    ]);
    Artisan::call('clear:unverified');
    expect(User::find($user->id))->toBeNull();
});

test('Does not delete just created user', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now(),
    ]);
    Artisan::call('clear:unverified');
    expect(User::find($user->id))->not->toBeNull();
});

test('Does not delete users after 1 Hours', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subHour(),
    ]);
    Artisan::call('clear:unverified');
    expect(User::find($user->id))->not->toBeNull();
});

test('Does not delete users after 23 Hours', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subHours(23),
    ]);
    Artisan::call('clear:unverified');
    expect(User::find($user->id))->not->toBeNull();
});

test('Does not delete users that are verified after 1 hour', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'created_at' => now()->subHour(),
    ]);
    Artisan::call('clear:unverified');
    expect(User::find($user->id))->not->toBeNull();
});

test('Does not delete users that are verified after 26 hours', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'created_at' => now()->subHours(26),
    ]);
    Artisan::call('clear:unverified');
    expect(User::find($user->id))->not->toBeNull();
});
