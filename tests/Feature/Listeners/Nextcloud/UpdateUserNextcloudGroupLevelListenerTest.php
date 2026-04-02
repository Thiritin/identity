<?php

use App\Enums\GroupUserLevel;
use App\Events\GroupUserUpdated;
use App\Jobs\Nextcloud\UpdateUserGroupLevelJob;
use App\Listeners\Nextcloud\UpdateUserNextcloudGroupLevel;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches UpdateUserGroupLevelJob when group has nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = $this->partialMock(UpdateUserNextcloudGroupLevel::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupUserUpdated($groupUser, GroupUserLevel::Member));

    Bus::assertDispatched(UpdateUserGroupLevelJob::class, function ($job) use ($group, $user) {
        return $job->group->id === $group->id
            && $job->user->id === $user->id
            && $job->oldLevel === GroupUserLevel::Member;
    });
});

it('does not dispatch job when group has no nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Admin]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = $this->partialMock(UpdateUserNextcloudGroupLevel::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupUserUpdated($groupUser, GroupUserLevel::Member));

    Bus::assertNotDispatched(UpdateUserGroupLevelJob::class);
});

it('does not dispatch job when shouldHandle returns false', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Admin]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new UpdateUserNextcloudGroupLevel();
    $listener->handle(new GroupUserUpdated($groupUser, GroupUserLevel::Member));

    Bus::assertNotDispatched(UpdateUserGroupLevelJob::class);
});
