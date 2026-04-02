<?php

namespace App\Observers;

use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Events\GroupUserUpdated;
use App\Models\GroupUser;

class GroupUserObserver
{
    public function created(GroupUser $groupUser): void
    {
        GroupUserAdded::dispatch($groupUser);
    }

    public function updated(GroupUser $groupUser): void
    {
        if ($groupUser->isDirty('level') || $groupUser->isDirty('can_manage_members')) {
            $original = $groupUser->getOriginal('level');
            $oldLevel = $original instanceof GroupUserLevel ? $original : GroupUserLevel::from($original);
            GroupUserUpdated::dispatch($groupUser, $oldLevel);
        }
    }

    public function deleted(GroupUser $groupUser): void
    {
        GroupUserRemoved::dispatch($groupUser);
    }
}
