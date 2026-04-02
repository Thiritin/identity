<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Listeners\CheckStaffGroupMembership;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Bus::fake();
    Event::fake([GroupUserAdded::class, GroupUserRemoved::class]);

    $this->staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
    ]);
});

it('adds user to staff group when added to a department', function () {
    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $department->id)->first();

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserAdded($groupUser));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('does not add user to staff group when added to a non-department group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['type' => GroupTypeEnum::Default]);

    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserAdded($groupUser));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('removes user from staff group when removed from last department', function () {
    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $department->id)->first();
    $department->users()->detach($user);

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserRemoved($groupUser));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('keeps user in staff group when removed from one department but still in another', function () {
    $user = User::factory()->create();
    $dept1 = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $dept2 = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $dept1->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $dept2->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $dept1->id)->first();
    $dept1->users()->detach($user);

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserRemoved($groupUser));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('handles missing staff group gracefully', function () {
    $this->staffGroup->delete();

    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $department->id)->first();

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserAdded($groupUser));

    expect(true)->toBeTrue();
});
