<?php

use App\Events\GroupUpdated;
use App\Jobs\Nextcloud\UpdateGroupJob;
use App\Listeners\Nextcloud\UpdateNextcloudGroup;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches UpdateGroupJob', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test']);

    $listener = $this->partialMock(UpdateNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupUpdated($group, ['name' => 'Old Name'], ['name']));

    Bus::assertDispatched(UpdateGroupJob::class, function ($job) use ($group) {
        return $job->group->id === $group->id && in_array('name', $job->changedFields);
    });
});

it('does not dispatch when shouldHandle returns false', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test']);

    $listener = new UpdateNextcloudGroup();
    $listener->handle(new GroupUpdated($group, [], ['name']));

    Bus::assertNotDispatched(UpdateGroupJob::class);
});
