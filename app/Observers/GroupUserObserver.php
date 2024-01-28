<?php

namespace App\Observers;

use App\Enums\GroupTypeEnum;
use App\Jobs\CheckStaffGroupMembershipJob;
use App\Models\GroupUser;

class GroupUserObserver
{
    public function created(GroupUser $groupUser): void
    {
        if ($groupUser->group->type === GroupTypeEnum::Department) {
            CheckStaffGroupMembershipJob::dispatch($groupUser->user);
        }
    }

    public function updated(GroupUser $groupUser): void
    {
    }

    public function deleted(GroupUser $groupUser): void
    {
        if ($groupUser->group->type === GroupTypeEnum::Department) {
            CheckStaffGroupMembershipJob::dispatch($groupUser->user);
        }
    }

    public function restored(GroupUser $groupUser): void
    {
    }

    public function forceDeleted(GroupUser $groupUser): void
    {
    }
}
