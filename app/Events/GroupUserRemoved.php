<?php

namespace App\Events;

use App\Models\GroupUser;
use Illuminate\Foundation\Events\Dispatchable;

class GroupUserRemoved
{
    use Dispatchable;

    public function __construct(
        public GroupUser $groupUser
    ) {}
}
