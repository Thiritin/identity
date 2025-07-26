<?php

namespace App\Domains\Staff\Models;

use App\Domains\Staff\Enums\GroupUserLevel;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupUser extends Pivot
{
    public $incrementing = false;

    protected $primaryKey = null;

    public $timestamps = false;

    protected $casts = [
        'level' => GroupUserLevel::class,
        'can_manage_users' => 'boolean',
    ];

    protected $fillable = [
        'level',
        'title',
        'can_manage_users',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function group()
    {
        return $this->hasOne(Group::class, 'id', 'group_id');
    }

    /**
     * Check if this user is a leader in this group
     */
    public function isLeader(): bool
    {
        return $this->level->isLeadership();
    }

    /**
     * Check if this user is a director of this group
     */
    public function isDirector(): bool
    {
        return in_array($this->level, [GroupUserLevel::Director, GroupUserLevel::DivisionDirector]);
    }

    /**
     * Check if this user is a team lead of this group
     */
    public function isTeamLead(): bool
    {
        return $this->level === GroupUserLevel::TeamLead;
    }

    /**
     * Check if this user can manage users in this group
     */
    public function canManageUsers(): bool
    {
        // Directors automatically have user management rights
        if ($this->isDirector()) {
            return true;
        }

        // Otherwise, check explicit permission
        return $this->can_manage_users;
    }
}
