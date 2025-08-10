<?php

namespace App\Observers;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Jobs\CheckStaffGroupMembershipJob;
use App\Jobs\Nextcloud\AddUserToGroupJob;
use App\Jobs\Nextcloud\RemoveUserFromGroupJob;
use App\Jobs\Nextcloud\UpdateUserGroupLevelJob;
use App\Models\GroupUser;
use Illuminate\Support\Facades\App;

class GroupUserObserver
{
    public function created(GroupUser $groupUser): void
    {
        if ($groupUser->group->type === GroupTypeEnum::Department) {
            CheckStaffGroupMembershipJob::dispatch($groupUser->user);
        }

        if (App::isLocal()) {
            return;
        }

        if (($groupUser->group->nextcloud_folder_name || $groupUser->group->parent?->nextcloud_folder_name) && ! app()->runningUnitTests()) {
            AddUserToGroupJob::dispatch($groupUser->group, $groupUser->user, $groupUser->level);
        }
    }

    public function updated(GroupUser $groupUser): void
    {
        if (App::isLocal()) {
            return;
        }

        if ($groupUser->group->nextcloud_folder_name && ! app()->runningUnitTests()) {
            if ($groupUser->isDirty('level')) {
                $oldLevel = GroupUserLevel::from($groupUser->getOriginal('level'));
                UpdateUserGroupLevelJob::dispatch($groupUser->group, $groupUser->user, $groupUser->level, $oldLevel);
            }
        }
    }

    public function deleted(GroupUser $groupUser): void
    {
        if ($groupUser->group->type === GroupTypeEnum::Department) {
            CheckStaffGroupMembershipJob::dispatch($groupUser->user);
        }

        if (App::isLocal()) {
            return;
        }

        if ($groupUser->group->nextcloud_folder_name && ! app()->runningUnitTests()) {
            RemoveUserFromGroupJob::dispatch($groupUser->group, $groupUser->user, $groupUser->group->type);
        }
    }
}
