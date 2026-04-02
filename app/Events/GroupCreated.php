<?php

namespace App\Events;

use App\Models\Group;
use Illuminate\Foundation\Events\Dispatchable;

class GroupCreated
{
    use Dispatchable;

    public function __construct(
        public Group $group
    ) {}
}
