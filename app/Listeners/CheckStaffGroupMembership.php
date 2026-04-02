<?php

namespace App\Listeners;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Models\Group;

class CheckStaffGroupMembership
{
    public function handle(GroupUserAdded|GroupUserRemoved $event): void
    {
        if ($event->groupUser->group->type !== GroupTypeEnum::Department) {
            return;
        }

        $staffGroup = Group::where('system_name', 'staff')->first();

        if (! $staffGroup) {
            return;
        }

        $user = $event->groupUser->user;
        $isMemberInAnyDepartment = $user->groups()->where('type', 'department')->exists();

        if ($isMemberInAnyDepartment) {
            $staffGroup->users()->syncWithoutDetaching([$user->id => ['level' => GroupUserLevel::Member]]);
        } else {
            $staffGroup->users()->detach($user->id);
        }
    }
}
