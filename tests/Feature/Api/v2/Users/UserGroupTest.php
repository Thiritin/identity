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

function actingAsApiUserForGroups(User $user, string $clientId, array $scopes = []): void
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

it('returns user groups with Groups.Read scope', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['type' => GroupTypeEnum::Department, 'name' => 'Art']);
    $group->users()->attach($user->id, ['level' => GroupUserLevel::Member, 'title' => 'Artist']);

    actingAsApiUserForGroups($user, 'app-one', ['Groups.Read']);

    $response = $this->getJson('/api/v2/users/me/groups')
        ->assertOk();

    expect($response->json())->toHaveCount(1);
    expect($response->json(0))->toHaveKeys(['id', 'name', 'type', 'slug', 'level', 'title']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/groups');
});

it('returns empty array when user has no groups', function () {
    $user = User::factory()->create();

    actingAsApiUserForGroups($user, 'app-one', ['Groups.Read']);

    $response = $this->getJson('/api/v2/users/me/groups')
        ->assertOk();

    expect($response->json())->toBeArray()->toBeEmpty();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/groups');
});

it('returns 403 without Groups.Read scope', function () {
    $user = User::factory()->create();

    actingAsApiUserForGroups($user, 'app-one', ['User.Read']);

    $response = $this->getJson('/api/v2/users/me/groups')
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/groups');
});

it('returns other user groups with Groups.Read.All scope', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create(['type' => GroupTypeEnum::Team, 'name' => 'Web']);
    $group->users()->attach($other->id, ['level' => GroupUserLevel::Member, 'title' => 'Developer']);

    actingAsApiUserForGroups($viewer, 'app-one', ['Groups.Read', 'Groups.Read.All']);

    $response = $this->getJson('/api/v2/users/' . $other->hashid . '/groups')
        ->assertOk();

    expect($response->json())->toHaveCount(1);
    expect($response->json('0.level'))->toBe('member');

    $this->assertMatchesOpenApiV2($response, '/users/{user}/groups');
});

it('returns 403 for other user without Groups.Read.All scope', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();

    actingAsApiUserForGroups($viewer, 'app-one', ['Groups.Read']);

    $response = $this->getJson('/api/v2/users/' . $other->hashid . '/groups')
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/groups');
});

it('includes correct fields in response', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['type' => GroupTypeEnum::Department, 'name' => 'Art']);
    $group->users()->attach($user->id, ['level' => GroupUserLevel::Member, 'title' => 'Artist']);

    actingAsApiUserForGroups($user, 'app-one', ['Groups.Read']);

    $response = $this->getJson('/api/v2/users/me/groups')
        ->assertOk();

    $item = $response->json(0);
    expect($item['id'])->toBe($group->hashid);
    expect($item['name'])->toBe('Art');
    expect($item['type'])->toBe('department');
    expect($item['slug'])->toBe($group->slug);
    expect($item['level'])->toBe('member');
    expect($item['title'])->toBe('Artist');

    $this->assertMatchesOpenApiV2($response, '/users/{user}/groups');
});
