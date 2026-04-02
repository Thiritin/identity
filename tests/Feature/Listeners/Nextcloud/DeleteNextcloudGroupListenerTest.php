<?php

use App\Events\GroupDeleted;
use App\Jobs\Nextcloud\DeleteGroupJob;
use App\Listeners\Nextcloud\DeleteNextcloudGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches DeleteGroupJob', function () {
    Bus::fake();

    $listener = $this->partialMock(DeleteNextcloudGroup::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('shouldHandle')->andReturn(true);
    });
    $listener->handle(new GroupDeleted('abc123', 1));

    Bus::assertDispatched(DeleteGroupJob::class, function ($job) {
        return $job->groupHashid === 'abc123' && $job->groupId === 1;
    });
});

it('does not dispatch when shouldHandle returns false', function () {
    Bus::fake();

    $listener = new DeleteNextcloudGroup();
    $listener->handle(new GroupDeleted('abc123', 1));

    Bus::assertNotDispatched(DeleteGroupJob::class);
});
