<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Events\GroupUserUpdated;
use App\Listeners\SyncAutomatedSystemGroups;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Bus::fake();
    Event::fake([GroupUserAdded::class, GroupUserUpdated::class, GroupUserRemoved::class]);

    // team_leads/directors/division_directors created by migration; staff is not.
    $this->staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
    ]);
    $this->teamLeadsGroup = Group::where('system_name', 'team_leads')->firstOrFail();
    $this->directorsGroup = Group::where('system_name', 'directors')->firstOrFail();
    $this->divisionDirectorsGroup = Group::where('system_name', 'division_directors')->firstOrFail();
});

function fireAdded(GroupUser $gu): void
{
    (new SyncAutomatedSystemGroups())->handle(new GroupUserAdded($gu));
}

function fireRemoved(GroupUser $gu): void
{
    (new SyncAutomatedSystemGroups())->handle(new GroupUserRemoved($gu));
}

function pivotFor(User $user, Group $group): GroupUser
{
    return GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();
}

it('adds user to staff group when added to a department', function () {
    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);

    fireAdded(pivotFor($user, $department));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('does not add user to staff group when added to a non-department group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['type' => GroupTypeEnum::Default]);
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

    fireAdded(pivotFor($user, $group));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('removes user from staff group when removed from last department', function () {
    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $pivot = pivotFor($user, $department);
    $department->users()->detach($user);

    fireRemoved($pivot);

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('keeps user in staff group when still in another department', function () {
    $user = User::factory()->create();
    $dept1 = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $dept2 = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $dept1->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $dept2->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $pivot = pivotFor($user, $dept1);
    $dept1->users()->detach($user);

    fireRemoved($pivot);

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('adds user to directors group when assigned director in a department', function () {
    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $department->users()->attach($user, ['level' => GroupUserLevel::Director]);

    fireAdded(pivotFor($user, $department));

    expect($this->directorsGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('does not add director when level is director but group is a division', function () {
    $user = User::factory()->create();
    $division = Group::factory()->create(['type' => GroupTypeEnum::Division]);
    $division->users()->attach($user, ['level' => GroupUserLevel::Director]);

    fireAdded(pivotFor($user, $division));

    expect($this->directorsGroup->users()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('adds user to division_directors group when assigned division_director in a division', function () {
    $user = User::factory()->create();
    $division = Group::factory()->create(['type' => GroupTypeEnum::Division]);
    $division->users()->attach($user, ['level' => GroupUserLevel::DivisionDirector]);

    fireAdded(pivotFor($user, $division));

    expect($this->divisionDirectorsGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('does not add division_director when the group is a department', function () {
    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $department->users()->attach($user, ['level' => GroupUserLevel::DivisionDirector]);

    fireAdded(pivotFor($user, $department));

    expect($this->divisionDirectorsGroup->users()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('adds user to team_leads group when assigned team_lead in a team', function () {
    $user = User::factory()->create();
    $team = Group::factory()->create(['type' => GroupTypeEnum::Team]);
    $team->users()->attach($user, ['level' => GroupUserLevel::TeamLead]);

    fireAdded(pivotFor($user, $team));

    expect($this->teamLeadsGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('removes director when level changes back to member via update event', function () {
    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $department->users()->attach($user, ['level' => GroupUserLevel::Director]);
    (new SyncAutomatedSystemGroups())->handle(new GroupUserAdded(pivotFor($user, $department)));

    expect($this->directorsGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();

    // Demote to member
    $department->users()->updateExistingPivot($user->id, ['level' => GroupUserLevel::Member]);
    (new SyncAutomatedSystemGroups())->handle(
        new GroupUserUpdated(pivotFor($user, $department), GroupUserLevel::Director)
    );

    expect($this->directorsGroup->users()->where('user_id', $user->id)->exists())->toBeFalse();
    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('handles missing system groups gracefully', function () {
    $this->staffGroup->delete();
    $this->directorsGroup->delete();

    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $department->users()->attach($user, ['level' => GroupUserLevel::Director]);

    fireAdded(pivotFor($user, $department));

    expect(true)->toBeTrue();
});
