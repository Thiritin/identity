<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use App\Services\Auth\ApiGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

function actingAsGroupApiUser(User $user, string $clientId, array $scopes = []): void
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

beforeEach(function () {
    // Staff group must exist for isStaff()/listener logic.
    $this->staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
});

it('returns groups index as a bare array with no data envelope', function () {
    $user = User::factory()->create();
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($user, 'app-one', ['groups.read']);

    Group::factory()->department()->create(['name' => 'IT'])->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $response = $this->getJson('/api/v2/groups');

    $response->assertOk();
    $body = $response->json();
    expect($body)->toBeArray();
    expect(array_is_list($body))->toBeTrue();
});

it('returns group members list as a bare array with no data envelope', function () {
    $user = User::factory()->create();
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($user, 'app-one', ['groups.read']);

    $department = Group::factory()->department()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $response = $this->getJson('/api/v2/groups/' . $department->hashid . '/members');

    $response->assertOk();
    $body = $response->json();
    expect($body)->toBeArray();
    expect(array_is_list($body))->toBeTrue();
});

it('adds a member by username (maps to users.name)', function () {
    $actor = User::factory()->create();
    $this->staffGroup->users()->attach($actor, ['level' => GroupUserLevel::Director]);
    actingAsGroupApiUser($actor, 'app-one', ['groups.write', 'groups.update', 'groups.read']);

    $department = Group::factory()->department()->create();
    $department->users()->attach($actor, ['level' => GroupUserLevel::Director]);

    $target = User::factory()->create(['name' => 'jdoe', 'email_verified_at' => now()]);
    // Pre-promote target so allow_making_staff isn't relevant to this test.
    $this->staffGroup->users()->attach($target, ['level' => GroupUserLevel::Member]);

    $response = $this->postJson('/api/v2/groups/' . $department->hashid . '/members', [
        'username' => 'jdoe',
        'level' => 'member',
    ]);

    $response->assertCreated();
    expect($department->users()->where('user_id', $target->id)->exists())->toBeTrue();
});

it('rejects adding a member when no identifier is provided', function () {
    $actor = User::factory()->create();
    $this->staffGroup->users()->attach($actor, ['level' => GroupUserLevel::Director]);
    actingAsGroupApiUser($actor, 'app-one', ['groups.write', 'groups.update']);

    $department = Group::factory()->department()->create();
    $department->users()->attach($actor, ['level' => GroupUserLevel::Director]);

    $response = $this->postJson('/api/v2/groups/' . $department->hashid . '/members', [
        'level' => 'member',
    ]);

    $response->assertStatus(422);
});

it('rejects adding a non-staff user to a department without allow_making_staff', function () {
    $actor = User::factory()->create();
    $this->staffGroup->users()->attach($actor, ['level' => GroupUserLevel::Director]);
    actingAsGroupApiUser($actor, 'app-one', ['groups.write', 'groups.update']);

    $department = Group::factory()->department()->create();
    $department->users()->attach($actor, ['level' => GroupUserLevel::Director]);

    $target = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->postJson('/api/v2/groups/' . $department->hashid . '/members', [
        'user_id' => $target->hashid,
    ]);

    $response->assertStatus(422);
    expect($response->json('errors.allow_making_staff'))->not->toBeNull();
    expect($department->users()->where('user_id', $target->id)->exists())->toBeFalse();
    expect($this->staffGroup->users()->where('user_id', $target->id)->exists())->toBeFalse();
});

it('allows adding a non-staff user to a department when allow_making_staff is true', function () {
    $actor = User::factory()->create();
    $this->staffGroup->users()->attach($actor, ['level' => GroupUserLevel::Director]);
    actingAsGroupApiUser($actor, 'app-one', ['groups.write', 'groups.update']);

    $department = Group::factory()->department()->create();
    $department->users()->attach($actor, ['level' => GroupUserLevel::Director]);

    $target = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->postJson('/api/v2/groups/' . $department->hashid . '/members', [
        'user_id' => $target->hashid,
        'allow_making_staff' => true,
    ]);

    $response->assertCreated();
    expect($department->users()->where('user_id', $target->id)->exists())->toBeTrue();
    // Listener should have promoted to staff automatically for departments.
    expect($this->staffGroup->users()->where('user_id', $target->id)->exists())->toBeTrue();
});

it('rejects adding a non-staff user to a team without allow_making_staff', function () {
    $actor = User::factory()->create();
    $this->staffGroup->users()->attach($actor, ['level' => GroupUserLevel::Director]);
    actingAsGroupApiUser($actor, 'app-one', ['groups.write', 'groups.update']);

    $department = Group::factory()->department()->create();
    $department->users()->attach($actor, ['level' => GroupUserLevel::Director]);
    $team = Group::factory()->team()->create(['parent_id' => $department->id]);

    $target = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->postJson('/api/v2/groups/' . $team->hashid . '/members', [
        'user_id' => $target->hashid,
    ]);

    $response->assertStatus(422);
    expect($team->users()->where('user_id', $target->id)->exists())->toBeFalse();
    expect($this->staffGroup->users()->where('user_id', $target->id)->exists())->toBeFalse();
});

it('allows adding a non-staff user to a team with allow_making_staff and promotes them', function () {
    $actor = User::factory()->create();
    $this->staffGroup->users()->attach($actor, ['level' => GroupUserLevel::Director]);
    actingAsGroupApiUser($actor, 'app-one', ['groups.write', 'groups.update']);

    $department = Group::factory()->department()->create();
    $department->users()->attach($actor, ['level' => GroupUserLevel::Director]);
    $team = Group::factory()->team()->create(['parent_id' => $department->id]);

    $target = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->postJson('/api/v2/groups/' . $team->hashid . '/members', [
        'user_id' => $target->hashid,
        'allow_making_staff' => true,
    ]);

    $response->assertCreated();
    expect($team->users()->where('user_id', $target->id)->exists())->toBeTrue();
    // Team additions must also explicitly promote to staff since the listener only fires on departments.
    expect($this->staffGroup->users()->where('user_id', $target->id)->exists())->toBeTrue();
});

it('allows adding an already-staff user to a department without allow_making_staff', function () {
    $actor = User::factory()->create();
    $this->staffGroup->users()->attach($actor, ['level' => GroupUserLevel::Director]);
    actingAsGroupApiUser($actor, 'app-one', ['groups.write', 'groups.update']);

    $department = Group::factory()->department()->create();
    $department->users()->attach($actor, ['level' => GroupUserLevel::Director]);

    $target = User::factory()->create(['email_verified_at' => now()]);
    $this->staffGroup->users()->attach($target, ['level' => GroupUserLevel::Member]);

    $response = $this->postJson('/api/v2/groups/' . $department->hashid . '/members', [
        'user_id' => $target->hashid,
    ]);

    $response->assertCreated();
    expect($department->users()->where('user_id', $target->id)->exists())->toBeTrue();
});

it('returns groups tree as a bare array with no data envelope', function () {
    $user = User::factory()->create();
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($user, 'app-one', ['groups.read']);

    $response = $this->getJson('/api/v2/groups/tree');

    $response->assertOk();
    $body = $response->json();
    expect($body)->toBeArray();
    expect(array_is_list($body))->toBeTrue();
});
