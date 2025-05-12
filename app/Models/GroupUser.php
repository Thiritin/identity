<?php

namespace App\Models;

use App\Enums\GroupUserLevel;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $group_id
 * @property int $user_id
 * @property GroupUserLevel $level
 * @property string|null $title
 * @property-read \App\Models\Group|null $group
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereUserId($value)
 *
 * @mixin \Eloquent
 */
class GroupUser extends Pivot
{
    public $incrementing = false;

    protected $primaryKey = null;

    public $timestamps = false;

    protected $casts = [
        'level' => GroupUserLevel::class,
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
        return $this->isOwner() || $this->level === GroupUserLevel::Admin;
    }

    public function isModerator(): bool
    {
        return $this->isAdmin() || $this->level === GroupUserLevel::Moderator;
    }

    public function isMember(): bool
    {
        return $this->isModerator() || $this->level === GroupUserLevel::Member;
    }
}
