<?php

namespace App\Domains\Staff\Models;

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Domains\Staff\Enums\GroupUserLevel;
use App\Domains\User\Models\User;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mtvs\EloquentHashids\HasHashid;
use Mtvs\EloquentHashids\HashidRouting;

/**
 * App\Domains\Staff\Models\Group.
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
    use HashidRouting;

    protected $appends = ['hashid', 'logo_url'];

    protected $guarded = [];

    protected $casts = [
        'type' => GroupTypeEnum::class,
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using(GroupUser::class)
            ->withPivot([
                'level',
                'title',
                'can_manage_users',
            ]);
    }

    /**
     * Get the leadership for this group (Directors, Division Directors, Team Leads)
     */
    public function leadership()
    {
        return $this->users()->wherePivotIn('level', [
            GroupUserLevel::Director->value,
            GroupUserLevel::DivisionDirector->value,
            GroupUserLevel::TeamLead->value,
        ]);
    }

    /**
     * Get users who can manage other users in this group
     */
    public function userManagers()
    {
        return $this->users()->where(function ($query) {
            $query->wherePivotIn('level', [
                GroupUserLevel::Director->value,
                GroupUserLevel::DivisionDirector->value,
            ])->orWhere('can_manage_users', true);
        });
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

    /**
     * Check if a user is a member of this group
     */
    public function isMember(User $user): bool
    {
        return $this->users->contains($user);
    }

    /**
     * Check if a user can manage users in this group
     */
    public function userCanManageUsers(User $user): bool
    {
        $member = $this->users->find($user);
        if (!$member) {
            return false;
        }

        return $member->pivot->canManageUsers();
    }

    /**
     * Check if a user is a director of this group
     */
    public function userIsDirector(User $user): bool
    {
        $member = $this->users->find($user);
        if (!$member) {
            return false;
        }

        return $member->pivot->isDirector();
    }

    /**
     * Check if a user is a leader in this group
     */
    public function userIsLeader(User $user): bool
    {
        $member = $this->users->find($user);
        if (!$member) {
            return false;
        }

        return $member->pivot->isLeader();
    }

    public function children()
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(__CLASS__, 'parent_id');
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors (parent, grandparent, etc.)
     */
    public function ancestors()
    {
        return $this->parent ? collect([$this->parent])->merge($this->parent->ancestors()) : collect();
    }

    /**
     * Check if this group can have a specific type as parent
     */
    public function canHaveParentType(GroupTypeEnum $parentType): bool
    {
        return $this->type->canHaveParent($parentType);
    }

    /**
     * Get the full hierarchy path
     */
    public function getHierarchyPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }
}
