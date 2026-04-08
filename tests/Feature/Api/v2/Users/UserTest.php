<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use App\Services\Auth\ApiGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\ValidatesOpenApiV2;

uses(RefreshDatabase::class, ValidatesOpenApiV2::class);

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

it('returns own profile with User.Read scope', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['User.Read']);

    $response = $this->getJson('/api/v2/users/me')
        ->assertOk()
        ->assertJsonStructure(['id', 'name', 'email', 'email_verified', 'avatar'])
        ->assertJson([
            'id' => $user->hashid,
            'name' => $user->name,
            'email' => $user->email,
        ]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}');
});

it('returns profile with include=groups when Groups.Read scope present', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $group->users()->attach($user, ['level' => GroupUserLevel::Member, 'title' => null]);

    actingAsApiUser($user, 'app-one', ['User.Read', 'Groups.Read']);

    $response = $this->getJson('/api/v2/users/me?include=groups')
        ->assertOk()
        ->assertJsonStructure(['id', 'name', 'email', 'email_verified', 'avatar', 'groups']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}');

    $groups = $response->json('groups');
    expect($groups)->toHaveCount(1);
    expect($groups[0])->toHaveKeys(['id', 'name', 'type', 'slug', 'level', 'title']);
});

it('does not include groups without Groups.Read scope', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['User.Read']);

    $response = $this->getJson('/api/v2/users/me?include=groups')
        ->assertOk();

    expect($response->json())->not->toHaveKey('groups');

    $this->assertMatchesOpenApiV2($response, '/users/{user}');
});

it('returns 403 without User.Read scope', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['other.scope']);

    $response = $this->getJson('/api/v2/users/me')
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}');
});

it('resolves me alias to authenticated user', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['User.Read']);

    $response = $this->getJson('/api/v2/users/me')
        ->assertOk()
        ->assertJson(['id' => $user->hashid]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}');
});

it('resolves hashid to specific user', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['User.Read']);

    $response = $this->getJson('/api/v2/users/' . $user->hashid)
        ->assertOk()
        ->assertJson(['id' => $user->hashid]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}');
});

it('returns 404 for invalid hashid', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['User.Read', 'User.Read.All']);

    $response = $this->getJson('/api/v2/users/INVALIDHASHID')
        ->assertNotFound();

    $this->assertMatchesOpenApiV2($response, '/users/{user}');
});

it('returns other user profile with User.Read.All scope', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();
    actingAsApiUser($viewer, 'app-one', ['User.Read', 'User.Read.All']);

    $response = $this->getJson('/api/v2/users/' . $other->hashid)
        ->assertOk()
        ->assertJson(['id' => $other->hashid]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}');
});

it('returns 403 for other user without User.Read.All scope', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();
    actingAsApiUser($viewer, 'app-one', ['User.Read']);

    $response = $this->getJson('/api/v2/users/' . $other->hashid)
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}');
});
