<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupUpdated;
use App\Jobs\Nextcloud\UpdateGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class UpdateNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupUpdated $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        UpdateGroupJob::dispatch($event->group, $event->originalData, $event->changedFields);
    }
}
