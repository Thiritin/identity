<?php

namespace App\Observers;

use App\Events\GroupCreated;
use App\Events\GroupDeleted;
use App\Events\GroupUpdated;
use App\Models\Group;

class GroupObserver
{
    public function created(Group $group): void
    {
        GroupCreated::dispatch($group);
    }

    public function updated(Group $group): void
    {
        $changedFields = array_keys($group->getDirty());
        $relevantChanges = array_intersect($changedFields, ['nextcloud_folder_name', 'name']);

        if (! empty($relevantChanges)) {
            GroupUpdated::dispatch($group, $group->getOriginal(), $relevantChanges);
        }
    }

    public function deleted(Group $group): void
    {
        GroupDeleted::dispatch($group->hashid, $group->id);
    }
}
