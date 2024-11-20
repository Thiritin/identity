<?php

namespace App\Models;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mtvs\EloquentHashids\HasHashid;
use Mtvs\EloquentHashids\HashidRouting;

class Group extends Model
{
    use HasFactory;
    use HasHashid;
    use HashidRouting;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'hashid', // @phpstan-ignore rules.modelAppends
        'logo_url'
    ];

    protected $guarded = [];

    protected $casts = [
        'type' => GroupTypeEnum::class,
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(GroupUser::class)
            ->withPivot(
                [
                    'level',
                    'title',
                ]
            );
    }

    public function owner()
    {
        return $this->hasOneThrough(User::class, GroupUser::class, 'group_id', 'id', 'id', 'user_id')
            ->where('level', GroupUserLevel::Owner)
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
        $member = $this->users->find($user);
        if (! $member) {
            return false;
        }

        return $member->pivot->level == GroupUserLevel::Admin || $member->pivot->level == GroupUserLevel::Owner;
    }

    /**
     * @return HasMany<Group, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id');
    }
}
