<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupDeleted;
use App\Jobs\Nextcloud\DeleteGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class DeleteNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupDeleted $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        DeleteGroupJob::dispatch($event->groupHashid, $event->groupId);
    }
}
