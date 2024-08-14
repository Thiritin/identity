<?php

namespace App\Observers;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Services\NextcloudService;
use Illuminate\Support\Facades\Auth;

class GroupObserver
{
    public function created(Group $group)
    {
        if ($group->type === GroupTypeEnum::Team && $group->parent->nextcloud_folder_id) {
            NextcloudService::createGroup($group->hashid);
            // Parent->name / Group->name
            NextcloudService::setDisplayName($group->hashid, $group->parent->name.' / '.$group->name);
            // Add to parent group folder
            NextcloudService::addGroupToFolder($group->parent->nextcloud_folder_id, $group->hashid);
            return;
        }
        if (Auth::user()) {
            $group->users()->attach(Auth::user(), [
                "level" => GroupUserLevel::Owner
            ]);
        }
    }

    public function updated(Group $group): void
    {
        if ($group->isDirty('nextcloud_folder_name') && !app()->runningUnitTests()) {
            // Update or create the folder via nextcloud
            if ($group->nextcloud_folder_id) {
                NextcloudService::renameFolder($group->nextcloud_folder_id, $group->nextcloud_folder_name);
            } else {
                NextcloudService::createGroup($group->hashid);
                $group->nextcloud_folder_id = NextcloudService::createFolder($group->nextcloud_folder_name,
                    $group->hashid);
                $group->save();
                NextcloudService::setDisplayName($group->hashid, $group->name);
                // Add all users to the group
                $group->users->each(fn($user) => NextcloudService::addUserToGroup($group, $user));
                // Set Admin & Owner to aclmanagers
                $group->users->filter(fn($user) => in_array($user->pivot->level,
                    [GroupUserLevel::Admin, GroupUserLevel::Owner]))
                    ->each(fn($user) => NextcloudService::setManageAcl($group, $user, true));

            }
        }
        if ($group->nextcloud_folder_id && $group->isDirty('name') && !app()->runningUnitTests()) {
            // Update the display name of the group
            NextcloudService::setDisplayName($group->hashid, $group->name);
        }

        // Team update nextcloud dipslay name
        if ($group->type === GroupTypeEnum::Team && $group->isDirty('name') && $group->parent->nextcloud_folder_id) {
            NextcloudService::setDisplayName($group->hashid, $group->parent->name.' / '.$group->name);
        }
    }

    public function deleted(Group $group)
    {
        NextcloudService::deleteGroup($group->hashid);
    }
}
