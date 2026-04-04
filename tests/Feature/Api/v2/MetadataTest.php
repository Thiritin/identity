<?php

use App\Models\User;
use App\Models\UserAppMetadata;
use App\Services\Auth\ApiGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

function actingAsApiUser(User $user, string $clientId, array $scopes = []): void
{
    $guard = Mockery::mock(ApiGuard::class);
    $guard->shouldReceive('user')->andReturn($user);
    $guard->shouldReceive('check')->andReturn(true);
    $guard->shouldReceive('guest')->andReturn(false);
    $guard->shouldReceive('id')->andReturn($user->id);
    $guard->shouldReceive('hasUser')->andReturn(true);
    $guard->shouldReceive('getClientId')->andReturn($clientId);
    $guard->shouldReceive('getScopes')->andReturn($scopes);
    $guard->shouldReceive('setRequest')->andReturnSelf();

    Auth::extend('hydra', fn () => $guard);
    Auth::forgetGuards();
}

it('returns empty data when user has no metadata', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.read']);

    $this->getJson('/api/v2/metadata')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns all metadata keys for the authenticated user and app', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.read']);

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'theme', 'value' => 'dark']);
    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'locale', 'value' => 'en']);

    $this->getJson('/api/v2/metadata')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['key' => 'theme', 'value' => 'dark'])
        ->assertJsonFragment(['key' => 'locale', 'value' => 'en']);
});

it('returns a single metadata key', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.read']);

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'theme', 'value' => 'dark']);

    $this->getJson('/api/v2/metadata/theme')
        ->assertOk()
        ->assertJson(['key' => 'theme', 'value' => 'dark']);
});

it('returns 404 for a non-existent key', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.read']);

    $this->getJson('/api/v2/metadata/nonexistent')
        ->assertNotFound();
});

it('creates a new metadata key via PUT and returns 201', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    $this->putJson('/api/v2/metadata/theme', ['value' => 'dark'])
        ->assertCreated()
        ->assertJson(['key' => 'theme', 'value' => 'dark']);

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'theme',
        'value' => 'dark',
    ]);
});

it('updates an existing metadata key via PUT and returns 200', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'theme', 'value' => 'dark']);

    $this->putJson('/api/v2/metadata/theme', ['value' => 'light'])
        ->assertOk()
        ->assertJson(['key' => 'theme', 'value' => 'light']);

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'theme',
        'value' => 'light',
    ]);
});

it('deletes an existing metadata key and returns 204', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'theme', 'value' => 'dark']);

    $this->deleteJson('/api/v2/metadata/theme')
        ->assertNoContent();

    $this->assertDatabaseMissing('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'theme',
    ]);
});

it('returns 404 when deleting a non-existent key', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    $this->deleteJson('/api/v2/metadata/nonexistent')
        ->assertNotFound();
});

it('isolates metadata between apps', function () {
    $user = User::factory()->create();

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-a', 'key' => 'secret', 'value' => 'hidden']);

    actingAsApiUser($user, 'app-b', ['metadata.read']);

    $this->getJson('/api/v2/metadata')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->getJson('/api/v2/metadata/secret')
        ->assertNotFound();
});

it('requires metadata.read scope for GET index', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['other.scope']);

    $this->getJson('/api/v2/metadata')
        ->assertForbidden();
});

it('requires metadata.read scope for GET show', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['other.scope']);

    $this->getJson('/api/v2/metadata/theme')
        ->assertForbidden();
});

it('requires metadata.write scope for PUT', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.read']);

    $this->putJson('/api/v2/metadata/theme', ['value' => 'dark'])
        ->assertForbidden();
});

it('requires metadata.write scope for DELETE', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.read']);

    $this->deleteJson('/api/v2/metadata/theme')
        ->assertForbidden();
});

it('rejects value exceeding 65535 characters', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    $this->putJson('/api/v2/metadata/theme', ['value' => str_repeat('a', 65536)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['value']);
});

it('returns 401 for unauthenticated requests', function () {
    $this->getJson('/api/v2/metadata')
        ->assertUnauthorized();
});

it('accepts a valid future expires_at on upsert', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    $expiresAt = now()->addYears(3)->startOfSecond();

    $this->putJson('/api/v2/metadata/address', [
        'value' => '123 Main St',
        'expires_at' => $expiresAt->toIso8601String(),
    ])
        ->assertCreated()
        ->assertJson([
            'key' => 'address',
            'value' => '123 Main St',
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'address',
        'expires_at' => $expiresAt->toDateTimeString(),
    ]);
});

it('rejects expires_at in the past', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    $this->putJson('/api/v2/metadata/address', [
        'value' => '123 Main St',
        'expires_at' => now()->subDay()->toIso8601String(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expires_at']);
});

it('accepts null expires_at meaning never expires', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    $this->putJson('/api/v2/metadata/address', [
        'value' => '123 Main St',
        'expires_at' => null,
    ])
        ->assertCreated()
        ->assertJson(['expires_at' => null]);
});
