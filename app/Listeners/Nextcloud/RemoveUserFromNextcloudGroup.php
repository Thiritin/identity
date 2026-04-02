<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupUserRemoved;
use App\Jobs\Nextcloud\RemoveUserFromGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class RemoveUserFromNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupUserRemoved $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        $group = $event->groupUser->group;

        if (! $group->nextcloud_folder_name) {
            return;
        }

        RemoveUserFromGroupJob::dispatch($group, $event->groupUser->user, $group->type);
    }
}
