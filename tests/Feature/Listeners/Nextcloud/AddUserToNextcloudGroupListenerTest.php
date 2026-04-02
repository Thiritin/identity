<?php

use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Jobs\Nextcloud\AddUserToGroupJob;
use App\Listeners\Nextcloud\AddUserToNextcloudGroup;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches AddUserToGroupJob when group has nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = $this->partialMock(AddUserToNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupUserAdded($groupUser));

    Bus::assertDispatched(AddUserToGroupJob::class, function ($job) use ($group, $user) {
        return $job->group->id === $group->id
            && $job->user->id === $user->id
            && $job->level === GroupUserLevel::Member;
    });
});

it('dispatches AddUserToGroupJob when parent has nextcloud folder', function () {
    Bus::fake();

    $parent = Group::factory()->create(['nextcloud_folder_name' => 'Parent Folder']);
    $group = Group::factory()->create(['parent_id' => $parent->id]);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = $this->partialMock(AddUserToNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupUserAdded($groupUser));

    Bus::assertDispatched(AddUserToGroupJob::class);
});

it('does not dispatch job when group has no nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = $this->partialMock(AddUserToNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupUserAdded($groupUser));

    Bus::assertNotDispatched(AddUserToGroupJob::class);
});

it('does not dispatch job when shouldHandle returns false', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new AddUserToNextcloudGroup();
    $listener->handle(new GroupUserAdded($groupUser));

    Bus::assertNotDispatched(AddUserToGroupJob::class);
});
