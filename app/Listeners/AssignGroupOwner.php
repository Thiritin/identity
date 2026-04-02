<?php

namespace App\Listeners;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupCreated;
use Illuminate\Support\Facades\Auth;

class AssignGroupOwner
{
    private function initialLevel(GroupCreated $event): GroupUserLevel
    {
        $group = $event->group;

        if ($group->isFunctionGroup()) {
            return GroupUserLevel::Member;
        }

        return match ($group->type) {
            GroupTypeEnum::Division => GroupUserLevel::DivisionDirector,
            GroupTypeEnum::Team => GroupUserLevel::TeamLead,
            default => GroupUserLevel::Director,
        };
    }

    public function handle(GroupCreated $event): void
    {
        $group = $event->group;

        if ($group->type === GroupTypeEnum::Team && $group->parent?->nextcloud_folder_id) {
            return;
        }

        $user = Auth::user();

        if (! $user) {
            return;
        }

        $group->users()->attach($user, [
            'level' => $this->initialLevel($event),
            'can_manage_members' => true,
        ]);
    }
}
