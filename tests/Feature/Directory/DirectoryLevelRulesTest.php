<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupDirectoryHierarchy(): array
{
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $division = Group::factory()->division()->create(['name' => 'Division', 'parent_id' => $root->id]);
    $department = Group::factory()->department()->create(['name' => 'Department', 'parent_id' => $division->id]);
    $team = Group::factory()->team()->create(['name' => 'Team', 'parent_id' => $department->id]);

    return [$staffGroup, $root, $division, $department, $team];
}

function makeStaffUserForTest($staffGroup, array $attrs = []): User
{
    $user = User::factory()->create($attrs);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    return $user;
}

// --- StoreMember level validation ---

test('admin can add DivisionDirector to division', function () {
    [$staffGroup, , $division] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('directory.members.store', $division), [
            'user_hashid' => $newUser->hashid,
            'level' => 'division_director',
        ])
        ->assertRedirect();

    expect($division->users()->where('user_id', $newUser->id)->first()->pivot->level)
        ->toBe(GroupUserLevel::DivisionDirector);
});

test('admin cannot add Member to division', function () {
    [$staffGroup, , $division] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('directory.members.store', $division), [
            'user_hashid' => $newUser->hashid,
            'level' => 'member',
        ])
        ->assertSessionHasErrors('level');
});

test('hr user can add Director to department', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $hr = makeStaffUserForTest($staffGroup, ['is_hr' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($hr)
        ->post(route('directory.members.store', $department), [
            'user_hashid' => $newUser->hashid,
            'level' => 'director',
        ])
        ->assertRedirect();
});

test('DD can add Director to child department', function () {
    [$staffGroup, , $division, $department] = setupDirectoryHierarchy();
    $dd = makeStaffUserForTest($staffGroup);
    // Attach to department first so SyncAutomatedSystemGroups keeps the user in staff
    $department->users()->attach($dd, ['level' => GroupUserLevel::Member]);
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    $newUser = User::factory()->create();

    $this->actingAs($dd)
        ->post(route('directory.members.store', $department), [
            'user_hashid' => $newUser->hashid,
            'level' => 'director',
        ])
        ->assertRedirect();
});

test('DD cannot add DD to own division (peer assignment)', function () {
    [$staffGroup, , $division, $department] = setupDirectoryHierarchy();
    $dd = makeStaffUserForTest($staffGroup);
    // Attach to department first so SyncAutomatedSystemGroups keeps the user in staff
    $department->users()->attach($dd, ['level' => GroupUserLevel::Member]);
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    $newUser = User::factory()->create();

    $this->actingAs($dd)
        ->post(route('directory.members.store', $division), [
            'user_hashid' => $newUser->hashid,
            'level' => 'division_director',
        ])
        ->assertSessionHasErrors('level');
});

test('Director cannot add Director to own department (peer assignment)', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    $newUser = User::factory()->create();

    $this->actingAs($director)
        ->post(route('directory.members.store', $department), [
            'user_hashid' => $newUser->hashid,
            'level' => 'director',
        ])
        ->assertSessionHasErrors('level');
});

test('delegate member in department can add TeamLead to child team', function () {
    [$staffGroup, , , $department, $team] = setupDirectoryHierarchy();
    $delegate = makeStaffUserForTest($staffGroup);
    $department->users()->attach($delegate, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($delegate)
        ->post(route('directory.members.store', $team), [
            'user_hashid' => $newUser->hashid,
            'level' => 'team_lead',
        ])
        ->assertRedirect();
});

test('plain member cannot add anyone', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $member = makeStaffUserForTest($staffGroup);
    $department->users()->attach($member, ['level' => GroupUserLevel::Member]);
    $newUser = User::factory()->create();

    $this->actingAs($member)
        ->post(route('directory.members.store', $department), [
            'user_hashid' => $newUser->hashid,
            'level' => 'member',
        ])
        ->assertForbidden();
});

// --- Group creation ---

test('DD can create department under division', function () {
    [$staffGroup, , $division, $department] = setupDirectoryHierarchy();
    $dd = makeStaffUserForTest($staffGroup);
    // Attach to department first so SyncAutomatedSystemGroups keeps the user in staff
    $department->users()->attach($dd, ['level' => GroupUserLevel::Member]);
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);

    $this->actingAs($dd)
        ->post(route('directory.departments.store', $division), ['name' => 'New Dept'])
        ->assertRedirect();

    expect(Group::where('name', 'New Dept')->where('type', GroupTypeEnum::Department)->exists())->toBeTrue();
});

test('Director can create team under department', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $this->actingAs($director)
        ->post(route('directory.teams.store', $department), ['name' => 'New Team'])
        ->assertRedirect();

    expect(Group::where('name', 'New Team')->where('type', GroupTypeEnum::Team)->exists())->toBeTrue();
});

test('Director cannot create department under division', function () {
    [$staffGroup, , $division, $department] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $this->actingAs($director)
        ->post(route('directory.departments.store', $division), ['name' => 'Nope'])
        ->assertForbidden();
});

test('cannot create child under team', function () {
    [$staffGroup, , , , $team] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);

    // Route doesn't exist for teams.store under a team, but testing via departments route
    $this->actingAs($admin)
        ->post(route('directory.departments.store', $team), ['name' => 'Nope'])
        ->assertForbidden();
});

// --- can_manage_members stripped on division ---

test('can_manage_members is stripped when adding to division', function () {
    [$staffGroup, , $division] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('directory.members.store', $division), [
            'user_hashid' => $newUser->hashid,
            'level' => 'division_director',
            'can_manage_members' => true,
        ])
        ->assertRedirect();

    expect($division->users()->where('user_id', $newUser->id)->first()->pivot->can_manage_members)->toBeFalse();
});

// --- UpdateMember level validation ---

test('Director can update member level to TeamLead in child team', function () {
    [$staffGroup, , , $department, $team] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    $member = User::factory()->create();
    $team->users()->attach($member, ['level' => GroupUserLevel::Member]);

    $this->actingAs($director)
        ->patch(route('directory.members.update', [$team, $member]), [
            'level' => 'team_lead',
            'title' => 'Lead',
            'can_manage_members' => false,
        ])
        ->assertRedirect();

    expect($team->users()->find($member)->pivot->level)->toBe(GroupUserLevel::TeamLead);
});

test('Director cannot set Director level in own department (peer)', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    $member = User::factory()->create();
    $department->users()->attach($member, ['level' => GroupUserLevel::Member]);

    $this->actingAs($director)
        ->patch(route('directory.members.update', [$department, $member]), [
            'level' => 'director',
            'title' => null,
            'can_manage_members' => false,
        ])
        ->assertSessionHasErrors('level');
});

test('admin can update member to any type-allowed level', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);
    $member = User::factory()->create();
    $department->users()->attach($member, ['level' => GroupUserLevel::Member]);

    $this->actingAs($admin)
        ->patch(route('directory.members.update', [$department, $member]), [
            'level' => 'director',
            'title' => 'Dept Director',
            'can_manage_members' => false,
        ])
        ->assertRedirect();

    expect($department->users()->find($member)->pivot->level)->toBe(GroupUserLevel::Director);
});

// --- Remove member scope ---

test('Director cannot remove peer Director', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $director1 = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director1, ['level' => GroupUserLevel::Director]);
    $director2 = User::factory()->create();
    $department->users()->attach($director2, ['level' => GroupUserLevel::Director]);

    $this->actingAs($director1)
        ->delete(route('directory.members.destroy', [$department, $director2]))
        ->assertForbidden();
});

test('DD can remove Director from child department', function () {
    [$staffGroup, , $division, $department] = setupDirectoryHierarchy();
    $dd = makeStaffUserForTest($staffGroup);
    // Attach to department first so SyncAutomatedSystemGroups keeps the user in staff
    $department->users()->attach($dd, ['level' => GroupUserLevel::Member]);
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    $director = User::factory()->create();
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $this->actingAs($dd)
        ->delete(route('directory.members.destroy', [$department, $director]))
        ->assertRedirect();

    expect($department->users()->where('user_id', $director->id)->exists())->toBeFalse();
});
