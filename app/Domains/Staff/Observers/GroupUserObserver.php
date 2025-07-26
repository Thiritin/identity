<?php

namespace App\Domains\Staff\Observers;

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Domains\Staff\Enums\GroupUserLevel;
use App\Jobs\CheckStaffGroupMembershipJob;
use App\Domains\Staff\Models\GroupUser;
use App\Domains\Auth\Services\NextcloudService;
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
            NextcloudService::addUserToGroup($groupUser->group, $groupUser->user);
            $allowAclManagement = $groupUser->can_manage_users && in_array($groupUser->level, [GroupUserLevel::Director, GroupUserLevel::DivisionDirector]);
            if ($allowAclManagement && $groupUser->group->type !== GroupTypeEnum::Team) {
                NextcloudService::setManageAcl($groupUser->group, $groupUser->user, $allowAclManagement);
            }
        }
    }

    public function updated(GroupUser $groupUser): void
    {
        if (App::isLocal()) {
            return;
        }
        if ($groupUser->group->nextcloud_folder_name && ! app()->runningUnitTests()) {
            if ($groupUser->isDirty('level') || $groupUser->isDirty('can_manage_users')) {
                $allowAclManagement = $groupUser->can_manage_users && in_array($groupUser->level, [GroupUserLevel::Director, GroupUserLevel::DivisionDirector]);
                NextcloudService::setManageAcl($groupUser->group, $groupUser->user, $allowAclManagement);
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
            NextcloudService::removeUserFromGroup($groupUser->group, $groupUser->user);
            if ($groupUser->group->type !== GroupTypeEnum::Team) {
                NextcloudService::setManageAcl($groupUser->group, $groupUser->user, false);
            }
        }
    }
}
