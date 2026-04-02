<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupUserUpdated;
use App\Jobs\Nextcloud\UpdateUserGroupLevelJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class UpdateUserNextcloudGroupLevel
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupUserUpdated $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        if (! $event->groupUser->group->nextcloud_folder_name) {
            return;
        }

        UpdateUserGroupLevelJob::dispatch(
            $event->groupUser->group,
            $event->groupUser->user,
            $event->groupUser->level,
            $event->oldLevel
        );
    }
}
