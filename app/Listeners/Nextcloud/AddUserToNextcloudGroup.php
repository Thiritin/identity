<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupUserAdded;
use App\Jobs\Nextcloud\AddUserToGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class AddUserToNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupUserAdded $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        $group = $event->groupUser->group;

        if (! $group->nextcloud_folder_name && ! $group->parent?->nextcloud_folder_name) {
            return;
        }

        AddUserToGroupJob::dispatch($group, $event->groupUser->user, $event->groupUser->level);
    }
}
