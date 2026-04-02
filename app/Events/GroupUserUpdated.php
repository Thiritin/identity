<?php

namespace App\Events;

use App\Enums\GroupUserLevel;
use App\Models\GroupUser;
use Illuminate\Foundation\Events\Dispatchable;

class GroupUserUpdated
{
    use Dispatchable;

    public function __construct(
        public GroupUser $groupUser,
        public GroupUserLevel $oldLevel
    ) {}
}
