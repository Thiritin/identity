<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('Deletes users after 24 Hours', function () {
    \App\Models\User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subHours(25),
    ]);
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
    \Illuminate\Support\Facades\Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(0, \App\Models\User::all());
});

test('Does not delete just created user', function () {
    \App\Models\User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now(),
    ]);
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
    \Illuminate\Support\Facades\Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
});

test('Does not delete users after 1 Hours', function () {
    \App\Models\User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subHour(),
    ]);
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
    \Illuminate\Support\Facades\Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
});

test('Does not delete users after 23 Hours', function () {
    \App\Models\User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subHours(23),
    ]);
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
    \Illuminate\Support\Facades\Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
});

test('Does not delete users that are verified after 1 hour', function () {
    \App\Models\User::factory()->create([
        'email_verified_at' => now(),
        'created_at' => now()->subHour(),
    ]);
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
    \Illuminate\Support\Facades\Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
});

test('Does not delete users that are verified after 26 hours', function () {
    \App\Models\User::factory()->create([
        'email_verified_at' => now(),
        'created_at' => now()->subHours(26),
    ]);
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
    \Illuminate\Support\Facades\Artisan::call('clear:unverified');
    \PHPUnit\Framework\assertCount(1, \App\Models\User::all());
});
