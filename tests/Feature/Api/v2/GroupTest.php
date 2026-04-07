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

    $this->assertMatchesOpenApiV2($response, '/groups');
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

    $this->assertMatchesOpenApiV2($response, '/groups/{group}/members');
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

    $this->assertMatchesOpenApiV2($response, '/groups/{group}/members', 'post');
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

    $this->assertMatchesOpenApiV2($response, '/groups/{group}/members', 'post');
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

    $this->assertMatchesOpenApiV2($response, '/groups/{group}/members', 'post');
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

    $this->assertMatchesOpenApiV2($response, '/groups/{group}/members', 'post');
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

    $this->assertMatchesOpenApiV2($response, '/groups/{group}/members', 'post');
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

    $this->assertMatchesOpenApiV2($response, '/groups/{group}/members', 'post');
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

    $this->assertMatchesOpenApiV2($response, '/groups/{group}/members', 'post');
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

    $this->assertMatchesOpenApiV2($response, '/groups/tree');
});

it('allows admin to create a division', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->staffGroup->users()->attach($admin, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($admin, 'app-one', ['groups.write', 'groups.read']);

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'division',
        'name' => 'New Division',
    ]);

    $response->assertCreated();
    $this->assertMatchesOpenApiV2($response, '/groups', 'post');
});

it('allows HR to create a division', function () {
    $hr = User::factory()->create(['is_hr' => true]);
    $this->staffGroup->users()->attach($hr, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($hr, 'app-one', ['groups.write', 'groups.read']);

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'division',
        'name' => 'Another Division',
    ]);

    $response->assertCreated();
    $this->assertMatchesOpenApiV2($response, '/groups', 'post');
});

it('denies non-admin creating a group', function () {
    $user = User::factory()->create();
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($user, 'app-one', ['groups.write']);

    $division = Group::factory()->division()->create();

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'department',
        'name' => 'Unauthorized Dept',
        'parent_id' => $division->hashid,
    ]);

    $response->assertForbidden();
});

it('requires parent_id when creating a department', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->staffGroup->users()->attach($admin, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($admin, 'app-one', ['groups.write']);

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'department',
        'name' => 'Missing Parent Dept',
    ]);

    $response->assertStatus(422);
    expect($response->json('errors.parent_id'))->not->toBeNull();
});

it('rejects department with a team as parent', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->staffGroup->users()->attach($admin, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($admin, 'app-one', ['groups.write']);

    $department = Group::factory()->department()->create();
    $team = Group::factory()->team()->create(['parent_id' => $department->id]);

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'department',
        'name' => 'Bad Parent Dept',
        'parent_id' => $team->hashid,
    ]);

    $response->assertStatus(422);
    expect($response->json('errors.parent_id'))->not->toBeNull();
});

it('allows division director to create a department in their division', function () {
    $divDirector = User::factory()->create();
    $this->staffGroup->users()->attach($divDirector, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($divDirector, 'app-one', ['groups.write', 'groups.read']);

    $division = Group::factory()->division()->create();
    $division->users()->attach($divDirector, ['level' => GroupUserLevel::DivisionDirector]);

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'department',
        'name' => 'New Department',
        'parent_id' => $division->hashid,
    ]);

    $response->assertCreated();
    expect($response->json('parent_id'))->toBe($division->hashid);
    $this->assertMatchesOpenApiV2($response, '/groups', 'post');
});

it('allows director to create a team in their department', function () {
    $director = User::factory()->create();
    $this->staffGroup->users()->attach($director, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($director, 'app-one', ['groups.write', 'groups.read']);

    $division = Group::factory()->division()->create();
    $department = Group::factory()->department()->create(['parent_id' => $division->id]);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'team',
        'name' => 'New Team',
        'parent_id' => $department->hashid,
    ]);

    $response->assertCreated();
    expect($response->json('parent_id'))->toBe($department->hashid);
    $this->assertMatchesOpenApiV2($response, '/groups', 'post');
});

it('denies director creating a department (wrong level)', function () {
    $director = User::factory()->create();
    $this->staffGroup->users()->attach($director, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($director, 'app-one', ['groups.write']);

    $division = Group::factory()->division()->create();
    $division->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'department',
        'name' => 'Unauthorized Dept',
        'parent_id' => $division->hashid,
    ]);

    $response->assertForbidden();
});

it('denies division director creating department in another division', function () {
    $divDirector = User::factory()->create();
    $this->staffGroup->users()->attach($divDirector, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($divDirector, 'app-one', ['groups.write']);

    $myDivision = Group::factory()->division()->create();
    $myDivision->users()->attach($divDirector, ['level' => GroupUserLevel::DivisionDirector]);

    $otherDivision = Group::factory()->division()->create();

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'department',
        'name' => 'Cross-Division Dept',
        'parent_id' => $otherDivision->hashid,
    ]);

    $response->assertForbidden();
});

it('rejects creating automated group type', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->staffGroup->users()->attach($admin, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($admin, 'app-one', ['groups.write']);

    $response = $this->postJson('/api/v2/groups', [
        'type' => 'automated',
        'name' => 'Fake System Group',
    ]);

    $response->assertStatus(422);
    expect($response->json('errors.type'))->not->toBeNull();
});

it('allows admin to update a department', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->staffGroup->users()->attach($admin, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($admin, 'app-one', ['groups.write', 'groups.read']);

    $department = Group::factory()->department()->create(['name' => 'Old Name']);

    $response = $this->putJson('/api/v2/groups/' . $department->hashid, [
        'name' => 'New Name',
    ]);

    $response->assertOk();
    expect($response->json('name'))->toBe('New Name');
    $this->assertMatchesOpenApiV2($response, '/groups/{group}', 'put');
});

it('allows division director to update a department in their division', function () {
    $divDirector = User::factory()->create();
    $this->staffGroup->users()->attach($divDirector, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($divDirector, 'app-one', ['groups.write', 'groups.read']);

    $division = Group::factory()->division()->create();
    $division->users()->attach($divDirector, ['level' => GroupUserLevel::DivisionDirector]);
    $department = Group::factory()->department()->create(['parent_id' => $division->id, 'name' => 'Old Name']);

    $response = $this->putJson('/api/v2/groups/' . $department->hashid, [
        'name' => 'Updated Name',
    ]);

    $response->assertOk();
    expect($response->json('name'))->toBe('Updated Name');
    $this->assertMatchesOpenApiV2($response, '/groups/{group}', 'put');
});

it('allows director to update a team in their department', function () {
    $director = User::factory()->create();
    $this->staffGroup->users()->attach($director, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($director, 'app-one', ['groups.write', 'groups.read']);

    $department = Group::factory()->department()->create();
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    $team = Group::factory()->team()->create(['parent_id' => $department->id, 'name' => 'Old Team']);

    $response = $this->putJson('/api/v2/groups/' . $team->hashid, [
        'name' => 'Updated Team',
    ]);

    $response->assertOk();
    expect($response->json('name'))->toBe('Updated Team');
    $this->assertMatchesOpenApiV2($response, '/groups/{group}', 'put');
});

it('allows team lead to update their team', function () {
    $teamLead = User::factory()->create();
    $this->staffGroup->users()->attach($teamLead, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($teamLead, 'app-one', ['groups.write', 'groups.read']);

    $department = Group::factory()->department()->create();
    $team = Group::factory()->team()->create(['parent_id' => $department->id, 'name' => 'Old Team']);
    $team->users()->attach($teamLead, ['level' => GroupUserLevel::TeamLead]);

    $response = $this->putJson('/api/v2/groups/' . $team->hashid, [
        'name' => 'Renamed Team',
    ]);

    $response->assertOk();
    expect($response->json('name'))->toBe('Renamed Team');
    $this->assertMatchesOpenApiV2($response, '/groups/{group}', 'put');
});

it('allows admin to delete a department', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->staffGroup->users()->attach($admin, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($admin, 'app-one', ['groups.delete']);

    $department = Group::factory()->department()->create();

    $response = $this->deleteJson('/api/v2/groups/' . $department->hashid);

    $response->assertNoContent();
    expect(Group::find($department->id))->toBeNull();
});

it('allows division director to delete a department in their division', function () {
    $divDirector = User::factory()->create();
    $this->staffGroup->users()->attach($divDirector, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($divDirector, 'app-one', ['groups.delete']);

    $division = Group::factory()->division()->create();
    $division->users()->attach($divDirector, ['level' => GroupUserLevel::DivisionDirector]);
    $department = Group::factory()->department()->create(['parent_id' => $division->id]);

    $response = $this->deleteJson('/api/v2/groups/' . $department->hashid);

    $response->assertNoContent();
    expect(Group::find($department->id))->toBeNull();
});

it('denies deleting root group even for admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->staffGroup->users()->attach($admin, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($admin, 'app-one', ['groups.delete']);

    $root = Group::where('type', GroupTypeEnum::Root)->first();

    $response = $this->deleteJson('/api/v2/groups/' . $root->hashid);

    $response->assertForbidden();
});

it('denies deleting automated group even for admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    actingAsGroupApiUser($admin, 'app-one', ['groups.delete']);

    $response = $this->deleteJson('/api/v2/groups/' . $this->staffGroup->hashid);

    $response->assertForbidden();
});

it('allows team lead to delete their team', function () {
    $teamLead = User::factory()->create();
    $this->staffGroup->users()->attach($teamLead, ['level' => GroupUserLevel::Member]);
    actingAsGroupApiUser($teamLead, 'app-one', ['groups.delete']);

    $department = Group::factory()->department()->create();
    $team = Group::factory()->team()->create(['parent_id' => $department->id]);
    $team->users()->attach($teamLead, ['level' => GroupUserLevel::TeamLead]);

    $response = $this->deleteJson('/api/v2/groups/' . $team->hashid);

    $response->assertNoContent();
});
