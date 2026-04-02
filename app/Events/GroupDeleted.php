<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class GroupDeleted
{
    use Dispatchable;

    public function __construct(
        public string $groupHashid,
        public int $groupId
    ) {}
}
