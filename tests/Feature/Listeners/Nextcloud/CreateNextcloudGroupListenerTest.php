<?php

use App\Enums\GroupTypeEnum;
use App\Events\GroupCreated;
use App\Jobs\Nextcloud\CreateGroupJob;
use App\Listeners\Nextcloud\CreateNextcloudGroup;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches CreateGroupJob for team group with parent nextcloud folder', function () {
    Bus::fake();

    $parent = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Team,
        'parent_id' => $parent->id,
    ]);
    $group = $group->refresh();

    $listener = $this->partialMock(CreateNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupCreated($group));

    Bus::assertDispatched(CreateGroupJob::class, function ($job) use ($group) {
        return $job->group->id === $group->id
            && $job->isTeamGroup === true
            && $job->parentFolderId === 42;
    });
});

it('does not dispatch for non-team groups', function () {
    Bus::fake();

    $group = Group::factory()->create(['type' => GroupTypeEnum::Default]);

    $listener = $this->partialMock(CreateNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupCreated($group));

    Bus::assertNotDispatched(CreateGroupJob::class);
});

it('does not dispatch for team group without parent nextcloud folder', function () {
    Bus::fake();

    $parent = Group::factory()->create();
    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Team,
        'parent_id' => $parent->id,
    ]);
    $group = $group->refresh();

    $listener = $this->partialMock(CreateNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupCreated($group));

    Bus::assertNotDispatched(CreateGroupJob::class);
});

it('does not dispatch when shouldHandle returns false', function () {
    Bus::fake();

    $parent = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Team,
        'parent_id' => $parent->id,
    ]);

    $listener = new CreateNextcloudGroup();
    $listener->handle(new GroupCreated($group));

    Bus::assertNotDispatched(CreateGroupJob::class);
});
