<?php

namespace App\Listeners;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupCreated;
use Illuminate\Support\Facades\Auth;

class AssignGroupOwner
{
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
            'level' => GroupUserLevel::Owner,
        ]);
    }
}
