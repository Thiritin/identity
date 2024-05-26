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
        'level' => GroupUserLevel::class
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function group()
    {
        return $this->hasOne(Group::class, 'id', 'group_id');
    }

    public function isOwner(): bool
    {
        return $this->level === GroupUserLevel::Owner;
    }

    public function isAdmin(): bool
    {
        return ($this->isOwner() || $this->level === GroupUserLevel::Admin);
    }

    public function isModerator(): bool
    {
        return ($this->isAdmin() || $this->level === GroupUserLevel::Moderator);
    }

    public function isMember(): bool
    {
        return ($this->isModerator() || $this->level === GroupUserLevel::Member);
    }
}
