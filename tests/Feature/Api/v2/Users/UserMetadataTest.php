<?php

use App\Models\User;
use App\Models\UserAppMetadata;
use App\Services\Auth\ApiGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\ValidatesOpenApiV2;

uses(RefreshDatabase::class, ValidatesOpenApiV2::class);

function actingAsMetadataApiUser(User $user, string $clientId, array $scopes = []): void
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
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.Read']);

    $response = $this->getJson('/api/v2/users/me/metadata')
        ->assertOk()
        ->assertJsonCount(0);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata');
});

it('returns all metadata keys for the authenticated user and app', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.Read']);

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'theme', 'value' => 'dark']);
    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'locale', 'value' => 'en']);

    $response = $this->getJson('/api/v2/users/me/metadata')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['key' => 'theme', 'value' => 'dark'])
        ->assertJsonFragment(['key' => 'locale', 'value' => 'en']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata');
});

it('returns a single metadata key', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.Read']);

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'theme', 'value' => 'dark']);

    $response = $this->getJson('/api/v2/users/me/metadata/theme')
        ->assertOk()
        ->assertJson(['key' => 'theme', 'value' => 'dark']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}');
});

it('returns 404 for a non-existent key', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.Read']);

    $response = $this->getJson('/api/v2/users/me/metadata/nonexistent')
        ->assertNotFound();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}');
});

it('creates a new metadata key via PUT and returns 201', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/metadata/theme', ['value' => 'dark'])
        ->assertCreated()
        ->assertJson(['key' => 'theme', 'value' => 'dark']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'put');

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'theme',
        'value' => 'dark',
    ]);
});

it('updates an existing metadata key via PUT and returns 200', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.ReadWrite']);

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'theme', 'value' => 'dark']);

    $response = $this->putJson('/api/v2/users/me/metadata/theme', ['value' => 'light'])
        ->assertOk()
        ->assertJson(['key' => 'theme', 'value' => 'light']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'put');

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'theme',
        'value' => 'light',
    ]);
});

it('deletes an existing metadata key and returns 204', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.ReadWrite']);

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-one', 'key' => 'theme', 'value' => 'dark']);

    $response = $this->deleteJson('/api/v2/users/me/metadata/theme')
        ->assertNoContent();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'delete');

    $this->assertDatabaseMissing('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'theme',
    ]);
});

it('returns 404 when deleting a non-existent key', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.ReadWrite']);

    $response = $this->deleteJson('/api/v2/users/me/metadata/nonexistent')
        ->assertNotFound();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'delete');
});

it('isolates metadata between apps', function () {
    $user = User::factory()->create();

    UserAppMetadata::create(['user_id' => $user->id, 'client_id' => 'app-a', 'key' => 'secret', 'value' => 'hidden']);

    actingAsMetadataApiUser($user, 'app-b', ['Metadata.Read']);

    $indexResponse = $this->getJson('/api/v2/users/me/metadata')
        ->assertOk()
        ->assertJsonCount(0);

    $this->assertMatchesOpenApiV2($indexResponse, '/users/{user}/metadata');

    $showResponse = $this->getJson('/api/v2/users/me/metadata/secret')
        ->assertNotFound();

    $this->assertMatchesOpenApiV2($showResponse, '/users/{user}/metadata/{key}');
});

it('requires Metadata.Read scope for GET index', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['other.scope']);

    $response = $this->getJson('/api/v2/users/me/metadata')
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata');
});

it('requires Metadata.Read scope for GET show', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['other.scope']);

    $response = $this->getJson('/api/v2/users/me/metadata/theme')
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}');
});

it('requires Metadata.ReadWrite scope for PUT', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.Read']);

    $response = $this->putJson('/api/v2/users/me/metadata/theme', ['value' => 'dark'])
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'put');
});

it('requires Metadata.ReadWrite scope for DELETE', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.Read']);

    $response = $this->deleteJson('/api/v2/users/me/metadata/theme')
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'delete');
});

it('rejects value exceeding 65535 characters', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/metadata/theme', ['value' => str_repeat('a', 65536)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['value']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'put');
});

it('returns 401 for unauthenticated requests', function () {
    $response = $this->getJson('/api/v2/users/me/metadata')
        ->assertUnauthorized();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata');
});

it('accepts a valid future expires_at on upsert', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.ReadWrite']);

    $expiresAt = now()->addYears(3)->startOfSecond();

    $response = $this->putJson('/api/v2/users/me/metadata/address', [
        'value' => '123 Main St',
        'expires_at' => $expiresAt->toIso8601String(),
    ])
        ->assertCreated()
        ->assertJson([
            'key' => 'address',
            'value' => '123 Main St',
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'put');

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'address',
        'expires_at' => $expiresAt->toDateTimeString(),
    ]);
});

it('rejects expires_at in the past', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/metadata/address', [
        'value' => '123 Main St',
        'expires_at' => now()->subDay()->toIso8601String(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expires_at']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'put');
});

it('accepts null expires_at meaning never expires', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/metadata/address', [
        'value' => '123 Main St',
        'expires_at' => null,
    ])
        ->assertCreated()
        ->assertJson(['expires_at' => null]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'put');

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'address',
        'expires_at' => null,
    ]);
});

it('returns expires_at on GET show', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.Read']);

    $expiresAt = now()->addYear()->startOfSecond();
    UserAppMetadata::create([
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'address',
        'value' => '123 Main St',
        'expires_at' => $expiresAt,
    ]);

    $response = $this->getJson('/api/v2/users/me/metadata/address')
        ->assertOk()
        ->assertJson(['expires_at' => $expiresAt->toIso8601String()]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}');
});

it('clears expires_at when upsert omits the field', function () {
    $user = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.ReadWrite']);

    UserAppMetadata::create([
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'address',
        'value' => '123 Main St',
        'expires_at' => now()->addYear(),
    ]);

    $response = $this->putJson('/api/v2/users/me/metadata/address', ['value' => '456 Oak Ave'])
        ->assertOk()
        ->assertJson(['expires_at' => null]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/metadata/{key}', 'put');

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'address',
        'value' => '456 Oak Ave',
        'expires_at' => null,
    ]);
});

it('returns 403 when accessing another user metadata', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    actingAsMetadataApiUser($user, 'app-one', ['Metadata.Read']);

    UserAppMetadata::create(['user_id' => $other->id, 'client_id' => 'app-one', 'key' => 'theme', 'value' => 'dark']);

    $this->getJson('/api/v2/users/' . $other->hashid . '/metadata')
        ->assertForbidden();

    $this->getJson('/api/v2/users/' . $other->hashid . '/metadata/theme')
        ->assertForbidden();
});
