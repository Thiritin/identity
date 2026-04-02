<?php

namespace App\Events;

use App\Models\GroupUser;
use Illuminate\Foundation\Events\Dispatchable;

class GroupUserAdded
{
    use Dispatchable;

    public function __construct(
        public GroupUser $groupUser
    ) {}
}
