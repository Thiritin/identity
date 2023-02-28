<?php

namespace App\Observers;

use App\Models\GroupUser;

class GroupUserObserver
{
    public function created(GroupUser $groupUser): void
    {
        event(GroupUserInvivted::class);
    }

    public function updated(GroupUser $groupUser): void
    {
    }

    public function deleted(GroupUser $groupUser): void
    {
    }

    public function restored(GroupUser $groupUser): void
    {
    }

    public function forceDeleted(GroupUser $groupUser): void
    {
    }
}
