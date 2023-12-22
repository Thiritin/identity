<?php

namespace App\Models;

use App\Enums\GroupUserLevel;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupUser extends Pivot
{
    public $incrementing = false;
    protected $primaryKey = null;

    protected $casts = [
        'level' => GroupUserLevel::class
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function group()
    {
        return $this->hasOne(Group::class);
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

    public function isInvited(): bool
    {
        return ($this->level === GroupUserLevel::Invited);
    }
}
