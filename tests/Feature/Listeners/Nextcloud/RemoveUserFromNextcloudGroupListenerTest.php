<?php

use App\Enums\GroupUserLevel;
use App\Events\GroupUserRemoved;
use App\Jobs\Nextcloud\RemoveUserFromGroupJob;
use App\Listeners\Nextcloud\RemoveUserFromNextcloudGroup;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches RemoveUserFromGroupJob when group has nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();
    $group->users()->detach($user);

    $listener = $this->partialMock(RemoveUserFromNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupUserRemoved($groupUser));

    Bus::assertDispatched(RemoveUserFromGroupJob::class, function ($job) use ($group, $user) {
        return $job->group->id === $group->id && $job->user->id === $user->id;
    });
});

it('does not dispatch job when group has no nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = $this->partialMock(RemoveUserFromNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupUserRemoved($groupUser));

    Bus::assertNotDispatched(RemoveUserFromGroupJob::class);
});

it('does not dispatch job when shouldHandle returns false', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new RemoveUserFromNextcloudGroup();
    $listener->handle(new GroupUserRemoved($groupUser));

    Bus::assertNotDispatched(RemoveUserFromGroupJob::class);
});
