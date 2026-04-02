<?php

namespace App\Models;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Concerns\HasHashid;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * App\Models\Group.
 *
 * @property string $id
 * @property string $name
 * @property string $logo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 *
 * @method static Builder|Group newModelQuery()
 * @method static Builder|Group newQuery()
 * @method static Builder|Group query()
 * @method static Builder|Group whereCreatedAt($value)
 * @method static Builder|Group whereId($value)
 * @method static Builder|Group whereLogo($value)
 * @method static Builder|Group whereName($value)
 * @method static Builder|Group whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Group extends Model
{
    use HasFactory;
    use HasHashid;

    public const FUNCTION_GROUP_SYSTEM_NAMES = ['devops', 'staff', 'directors'];

    protected $appends = ['hashid', 'logo_url'];

    protected $guarded = [];

    protected $casts = [
        'type' => GroupTypeEnum::class,
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using(GroupUser::class)
            ->withPivot(
                [
                    'level',
                    'title',
                    'can_manage_members',
                    'credit_as',
                ]
            );
    }

    public function owner()
    {
        return $this->hasOneThrough(User::class, GroupUser::class, 'group_id', 'id', 'id', 'user_id')
            ->whereIn('level', [
                GroupUserLevel::DivisionDirector->value,
                GroupUserLevel::Director->value,
                GroupUserLevel::TeamLead->value,
            ])
            ->select(['name']);
    }

    public function apps()
    {
        return $this->belongsToMany(App::class);
    }

    public function getHashidsConnection()
    {
        return 'group';
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $prefix = '';
        if ($this->parent) {
            $prefix = $this->parent->name . ' / ';
        }
        $this->attributes['slug'] = $prefix . Str::slug($value);
    }

    public function getLogoUrlAttribute()
    {
        return (is_null($this->logo)) ? null : Storage::url('avatars/' . $this->logo);
    }

    public function isMember(User $user): bool
    {
        return $this->users->contains($user);
    }

    public function isAdmin(User $user)
    {
        return $this->canManageMembers($user);
    }

    public function canManageMembers(User $user): bool
    {
        $member = $this->users->find($user);
        if (! $member) {
            return false;
        }

        $level = $member->pivot->level instanceof GroupUserLevel
            ? $member->pivot->level
            : GroupUserLevel::from($member->pivot->level);

        return (bool) $member->pivot->can_manage_members
            || $level->isLeadRole();
    }

    public function isFunctionGroup(): bool
    {
        return in_array((string) $this->system_name, self::FUNCTION_GROUP_SYSTEM_NAMES, true);
    }

    public function children()
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(__CLASS__, 'parent_id');
    }
}
