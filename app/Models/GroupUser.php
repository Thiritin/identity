<?php

namespace App\Models;

use App\Enums\GroupUserLevel;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupUser extends Pivot
{
    public $incrementing = false;

    protected $primaryKey = null;

    public $timestamps = false;

    protected $casts = [
        'level' => GroupUserLevel::class,
        'can_manage_members' => 'boolean',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function group()
    {
        return $this->hasOne(Group::class, 'id', 'group_id');
    }

    public function isAdmin(): bool
    {
        return $this->canManageMembers();
    }

    public function isMember(): bool
    {
        return true;
    }

    public function canManageMembers(): bool
    {
        return $this->can_manage_members
            || $this->level->isLeadRole();
    }
}
