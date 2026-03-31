<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('Deletes users after 24 Hours', function () {
    User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subHours(25),
    ]);
    \PHPUnit\Framework\assertCount(1, User::all());
    Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(0, User::all());
});

test('Does not delete just created user', function () {
    User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now(),
    ]);
    \PHPUnit\Framework\assertCount(1, User::all());
    Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, User::all());
});

test('Does not delete users after 1 Hours', function () {
    User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subHour(),
    ]);
    \PHPUnit\Framework\assertCount(1, User::all());
    Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, User::all());
});

test('Does not delete users after 23 Hours', function () {
    User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subHours(23),
    ]);
    \PHPUnit\Framework\assertCount(1, User::all());
    Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, User::all());
});

test('Does not delete users that are verified after 1 hour', function () {
    User::factory()->create([
        'email_verified_at' => now(),
        'created_at' => now()->subHour(),
    ]);
    \PHPUnit\Framework\assertCount(1, User::all());
    Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, User::all());
});

test('Does not delete users that are verified after 26 hours', function () {
    User::factory()->create([
        'email_verified_at' => now(),
        'created_at' => now()->subHours(26),
    ]);
    \PHPUnit\Framework\assertCount(1, User::all());
    Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, User::all());
});
