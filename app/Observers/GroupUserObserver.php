<?php

namespace App\Observers;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Jobs\CheckStaffGroupMembershipJob;
use App\Models\GroupUser;
use App\Services\NextcloudService;

class GroupUserObserver
{
    public function created(GroupUser $groupUser): void
    {
        if ($groupUser->group->type === GroupTypeEnum::Department) {
            CheckStaffGroupMembershipJob::dispatch($groupUser->user);
        }
        if ($groupUser->group->nextcloud_folder_name) {
            NextcloudService::addUserToGroup($groupUser->group, $groupUser->user);
            $allowAclManagement = in_array($groupUser->level, [GroupUserLevel::Admin, GroupUserLevel::Owner]);
            if ($allowAclManagement) {
                NextcloudService::setManageAcl($groupUser->group, $groupUser->user, $allowAclManagement);
            }
        }
    }

    public function updated(GroupUser $groupUser): void
    {
        if ($groupUser->group->nextcloud_folder_name) {
            if ($groupUser->isDirty('level')) {
                $allowAclManagement = in_array($groupUser->level, [GroupUserLevel::Admin, GroupUserLevel::Owner]);
                NextcloudService::setManageAcl($groupUser->group, $groupUser->user, $allowAclManagement);
            }
        }
    }

    public function deleted(GroupUser $groupUser): void
    {
        if ($groupUser->group->type === GroupTypeEnum::Department) {
            CheckStaffGroupMembershipJob::dispatch($groupUser->user);
        }
        if ($groupUser->group->nextcloud_folder_name) {
            NextcloudService::removeUserFromGroup($groupUser->group, $groupUser->user);
            NextcloudService::setManageAcl($groupUser->group, $groupUser->user, false);
        }
    }

    public function restored(GroupUser $groupUser): void
    {
    }

    public function forceDeleted(GroupUser $groupUser): void
    {
    }
}
