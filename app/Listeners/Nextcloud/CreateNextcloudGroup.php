<?php

namespace App\Listeners\Nextcloud;

use App\Enums\GroupTypeEnum;
use App\Events\GroupCreated;
use App\Jobs\Nextcloud\CreateGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class CreateNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupCreated $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        $group = $event->group;

        if ($group->type !== GroupTypeEnum::Team || ! $group->parent?->nextcloud_folder_id) {
            return;
        }

        CreateGroupJob::dispatch($group, true, $group->parent->nextcloud_folder_id);
    }
}
