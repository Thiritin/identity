<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authorizer(): DirectoryAuthorizer
{
    return app(DirectoryAuthorizer::class);
}

function createHierarchy(): array
{
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $division = Group::factory()->division()->create(['parent_id' => $root->id]);
    $department = Group::factory()->department()->create(['parent_id' => $division->id]);
    $team = Group::factory()->team()->create(['parent_id' => $department->id]);

    return [$root, $division, $department, $team];
}

// --- hasGlobalPowers ---

test('admin has global powers', function () {
    $user = User::factory()->create(['is_admin' => true]);
    expect(authorizer()->hasGlobalPowers($user))->toBeTrue();
});

test('hr has global powers', function () {
    $user = User::factory()->create(['is_hr' => true]);
    expect(authorizer()->hasGlobalPowers($user))->toBeTrue();
});

test('regular user does not have global powers', function () {
    $user = User::factory()->create(['is_admin' => false, 'is_hr' => false]);
    expect(authorizer()->hasGlobalPowers($user))->toBeFalse();
});

// --- effectiveLevel ---

test('effectiveLevel returns lead role directly', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Director]);

    expect(authorizer()->effectiveLevel($user, $department))->toBe(GroupUserLevel::Director);
});

test('effectiveLevel returns topLeadLevel for delegate member', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);

    expect(authorizer()->effectiveLevel($user, $department))->toBe(GroupUserLevel::Director);
});

test('effectiveLevel returns null for plain member without delegation', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Member, 'can_manage_members' => false]);

    expect(authorizer()->effectiveLevel($user, $department))->toBeNull();
});

test('effectiveLevel returns null for non-member', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();

    expect(authorizer()->effectiveLevel($user, $department))->toBeNull();
});

test('effectiveLevel ignores can_manage_members on division', function () {
    [, $division] = createHierarchy();
    $user = User::factory()->create();
    $division->users()->attach($user, ['level' => GroupUserLevel::DivisionDirector, 'can_manage_members' => true]);

    // DivisionDirector is already a lead role — returned as-is
    expect(authorizer()->effectiveLevel($user, $division))->toBe(GroupUserLevel::DivisionDirector);
});

// --- assignableLevels ---

test('admin gets all levels allowed by group type', function () {
    [, , $department] = createHierarchy();
    $admin = User::factory()->create(['is_admin' => true]);

    $levels = authorizer()->assignableLevels($admin, $department);
    expect($levels)->toEqualCanonicalizing([GroupUserLevel::Director, GroupUserLevel::Member]);
});

test('hr gets all levels allowed by group type', function () {
    [, $division] = createHierarchy();
    $hr = User::factory()->create(['is_hr' => true]);

    $levels = authorizer()->assignableLevels($hr, $division);
    expect($levels)->toBe([GroupUserLevel::DivisionDirector]);
});

test('DD in division can assign Director and Member in child department', function () {
    [, $division, $department] = createHierarchy();
    $dd = User::factory()->create();
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);

    $levels = authorizer()->assignableLevels($dd, $department);
    expect($levels)->toEqualCanonicalizing([GroupUserLevel::Director, GroupUserLevel::Member]);
});

test('Director in department can assign TeamLead and Member in child team', function () {
    [, , $department, $team] = createHierarchy();
    $director = User::factory()->create();
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $levels = authorizer()->assignableLevels($director, $team);
    expect($levels)->toEqualCanonicalizing([GroupUserLevel::TeamLead, GroupUserLevel::Member]);
});

test('TeamLead in team can assign Member in own team', function () {
    [, , , $team] = createHierarchy();
    $lead = User::factory()->create();
    $team->users()->attach($lead, ['level' => GroupUserLevel::TeamLead]);

    $levels = authorizer()->assignableLevels($lead, $team);
    expect($levels)->toBe([GroupUserLevel::Member]);
});

test('delegate member in department gets Director assignable levels', function () {
    [, , $department, $team] = createHierarchy();
    $delegate = User::factory()->create();
    $department->users()->attach($delegate, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);

    $levels = authorizer()->assignableLevels($delegate, $team);
    expect($levels)->toEqualCanonicalizing([GroupUserLevel::TeamLead, GroupUserLevel::Member]);
});

test('unrelated user gets empty assignable levels', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();

    expect(authorizer()->assignableLevels($user, $department))->toBe([]);
});

test('DD cannot assign peer DD in own division', function () {
    [, $division] = createHierarchy();
    $dd = User::factory()->create();
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);

    // DD's assignableLevels = [Director, TeamLead, Member] ∩ Division's allowedLevels = [DivisionDirector] → empty
    expect(authorizer()->assignableLevels($dd, $division))->toBe([]);
});

// --- canManageMembers ---

test('admin can manage members anywhere', function () {
    [, , $department] = createHierarchy();
    $admin = User::factory()->create(['is_admin' => true]);
    expect(authorizer()->canManageMembers($admin, $department))->toBeTrue();
});

test('group lead can manage members', function () {
    [, , $department] = createHierarchy();
    $director = User::factory()->create();
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    expect(authorizer()->canManageMembers($director, $department))->toBeTrue();
});

test('parent lead can manage members in child', function () {
    [, $division, $department] = createHierarchy();
    $dd = User::factory()->create();
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    expect(authorizer()->canManageMembers($dd, $department))->toBeTrue();
});

test('delegate can manage members', function () {
    [, , $department] = createHierarchy();
    $delegate = User::factory()->create();
    $department->users()->attach($delegate, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);
    expect(authorizer()->canManageMembers($delegate, $department))->toBeTrue();
});

test('plain member cannot manage members', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    expect(authorizer()->canManageMembers($user, $department))->toBeFalse();
});

// --- canCreateChildGroup ---

test('DD can create department under division', function () {
    [, $division] = createHierarchy();
    $dd = User::factory()->create();
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    expect(authorizer()->canCreateChildGroup($dd, $division))->toBeTrue();
});

test('Director can create team under department', function () {
    [, , $department] = createHierarchy();
    $director = User::factory()->create();
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    expect(authorizer()->canCreateChildGroup($director, $department))->toBeTrue();
});

test('cannot create child under team (no child type)', function () {
    [, , , $team] = createHierarchy();
    $admin = User::factory()->create(['is_admin' => true]);
    expect(authorizer()->canCreateChildGroup($admin, $team))->toBeFalse();
});

test('delegate can create child group', function () {
    [, , $department] = createHierarchy();
    $delegate = User::factory()->create();
    $department->users()->attach($delegate, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);
    expect(authorizer()->canCreateChildGroup($delegate, $department))->toBeTrue();
});

test('plain member cannot create child group', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    expect(authorizer()->canCreateChildGroup($user, $department))->toBeFalse();
});

// --- levelAllowedByType ---

test('DivisionDirector allowed on division', function () {
    [, $division] = createHierarchy();
    expect(authorizer()->levelAllowedByType(GroupUserLevel::DivisionDirector, $division))->toBeTrue();
});

test('Member not allowed on division', function () {
    [, $division] = createHierarchy();
    expect(authorizer()->levelAllowedByType(GroupUserLevel::Member, $division))->toBeFalse();
});

test('any level allowed on unrestricted type', function () {
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    expect(authorizer()->levelAllowedByType(GroupUserLevel::DivisionDirector, $root))->toBeTrue();
    expect(authorizer()->levelAllowedByType(GroupUserLevel::Member, $root))->toBeTrue();
});
