<?php

namespace App\Observers;

use App\Enums\GroupUserLevel;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;

class GroupObserver
{
    public function created(Group $group)
    {
        if (Auth::user()) {
            $group->users()->attach(Auth::user(), [
                "level" => GroupUserLevel::Owner
            ]);
        }
    }
}
