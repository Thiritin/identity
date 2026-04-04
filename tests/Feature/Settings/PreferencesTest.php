<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates a valid preference', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.preferences.update'), [
            'key' => 'nsfw_content',
            'value' => true,
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Preference saved',
        ]);

    expect($user->fresh()->preferences)->toBe(['nsfw_content' => true]);
});

it('rejects an unknown preference key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.preferences.update'), [
            'key' => 'unknown_key',
            'value' => true,
        ])
        ->assertSessionHasErrors('key');
});

it('requires authentication', function () {
    $this->post(route('settings.preferences.update'), [
        'key' => 'nsfw_content',
        'value' => true,
    ])->assertRedirect();
});

it('updates locale preference', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.preferences.update'), [
            'key' => 'locale',
            'value' => 'de',
        ])
        ->assertRedirect();

    expect($user->fresh()->preferences)->toBe(['locale' => 'de']);
});

it('rejects invalid locale', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.preferences.update'), [
            'key' => 'locale',
            'value' => 'xx',
        ])
        ->assertSessionHasErrors('value');
});

it('updates theme preference', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.preferences.update'), [
            'key' => 'theme',
            'value' => 'dark',
        ])
        ->assertRedirect();

    expect($user->fresh()->preferences)->toBe(['theme' => 'dark']);
});

it('rejects invalid theme', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.preferences.update'), [
            'key' => 'theme',
            'value' => 'rainbow',
        ])
        ->assertSessionHasErrors('value');
});

it('user locale overrides browser locale', function () {
    $user = User::factory()->create(['preferences' => ['locale' => 'fr']]);

    $this->actingAs($user)
        ->get(route('settings.profile'), ['Accept-Language' => 'de'])
        ->assertSuccessful();

    expect(app()->getLocale())->toBe('fr');
});

it('toggles a preference off', function () {
    $user = User::factory()->create(['preferences' => ['nsfw_content' => true]]);

    $this->actingAs($user)
        ->post(route('settings.preferences.update'), [
            'key' => 'nsfw_content',
            'value' => false,
        ])
        ->assertRedirect();

    expect($user->fresh()->preferences)->toBe(['nsfw_content' => false]);
});
