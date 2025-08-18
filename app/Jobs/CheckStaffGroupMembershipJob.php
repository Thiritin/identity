<?php

namespace App\Jobs;

use App\Enums\GroupUserLevel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * CheckStaffGroupMembershipJob
 *
 * This job gets triggered when a user has been removed or added to a group and the group is of type department.
 * It will then check if the user is still in atleast one group of type department, if it is not, it will remove
 * the user from the staff group.
 */
class CheckStaffGroupMembershipJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private User $user) {}

    public function handle(): void
    {
        $staffGroup = \App\Models\Group::where('system_name', 'staff')->first();

        if (!$staffGroup) {
            return;
        }

        $isMemberInAnyDepartment = $this->user->groups()->where('type', 'department')->exists();
        if ($isMemberInAnyDepartment) {
            $staffGroup->users()->syncWithoutDetaching([$this->user->id => ['level' => GroupUserLevel::Member]]);
        } else {
            $staffGroup->users()->detach($this->user->id);
        }
    }
}
