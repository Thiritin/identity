<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates a valid preference', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('settings.preferences.update'), [
            'key' => 'nsfw_content',
            'value' => true,
        ])
        ->assertSuccessful();

    expect($user->fresh()->preferences)->toBe(['nsfw_content' => true]);
});

it('rejects an unknown preference key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('settings.preferences.update'), [
            'key' => 'unknown_key',
            'value' => true,
        ])
        ->assertUnprocessable();
});

it('requires authentication', function () {
    $this->postJson(route('settings.preferences.update'), [
        'key' => 'nsfw_content',
        'value' => true,
    ])->assertUnauthorized();
});

it('toggles a preference off', function () {
    $user = User::factory()->create(['preferences' => ['nsfw_content' => true]]);

    $this->actingAs($user)
        ->postJson(route('settings.preferences.update'), [
            'key' => 'nsfw_content',
            'value' => false,
        ])
        ->assertSuccessful();

    expect($user->fresh()->preferences)->toBe(['nsfw_content' => false]);
});
