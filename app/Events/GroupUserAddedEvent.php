<?php

namespace App\Events;

use App\Domains\Staff\Models\GroupUser;
use Illuminate\Foundation\Events\Dispatchable;

class GroupUserAddedEvent
{
    use Dispatchable;

    public GroupUser $groupUser;

    public function __construct(GroupUser $groupUser)
    {
        $this->groupUser = $groupUser;
    }
}
