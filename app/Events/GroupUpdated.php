<?php

namespace App\Events;

use App\Models\Group;
use Illuminate\Foundation\Events\Dispatchable;

class GroupUpdated
{
    use Dispatchable;

    public function __construct(
        public Group $group,
        public array $originalData,
        public array $changedFields
    ) {}
}
