<?php

use App\Models\User;
use App\Models\UserAppMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes rows whose expires_at is in the past', function () {
    $user = User::factory()->create();

    UserAppMetadata::create([
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'expired',
        'value' => 'gone',
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('metadata:prune-expired')->assertSuccessful();

    $this->assertDatabaseMissing('user_app_metadata', ['key' => 'expired']);
});

it('leaves rows with null expires_at untouched', function () {
    $user = User::factory()->create();

    UserAppMetadata::create([
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'forever',
        'value' => 'keep',
        'expires_at' => null,
    ]);

    $this->artisan('metadata:prune-expired')->assertSuccessful();

    $this->assertDatabaseHas('user_app_metadata', ['key' => 'forever']);
});

it('leaves rows with future expires_at untouched', function () {
    $user = User::factory()->create();

    UserAppMetadata::create([
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'future',
        'value' => 'keep',
        'expires_at' => now()->addYear(),
    ]);

    $this->artisan('metadata:prune-expired')->assertSuccessful();

    $this->assertDatabaseHas('user_app_metadata', ['key' => 'future']);
});
