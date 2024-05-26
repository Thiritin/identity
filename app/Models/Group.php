<?php

namespace App\Models;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
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
 * App\Models\Group.
 *
 * @property string $id
 * @property string $name
 * @property string $logo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 * @method static Builder|Group newModelQuery()
 * @method static Builder|Group newQuery()
 * @method static Builder|Group query()
 * @method static Builder|Group whereCreatedAt($value)
 * @method static Builder|Group whereId($value)
 * @method static Builder|Group whereLogo($value)
 * @method static Builder|Group whereName($value)
 * @method static Builder|Group whereUpdatedAt($value)
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
        "type" => GroupTypeEnum::class,
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using('App\Models\GroupUser')
            ->withPivot(
                [
                    'level',
                    'title',
                ]
            );
    }

    public function owner()
    {
        return $this->hasOneThrough(User::class, GroupUser::class, "group_id", "id", "id", "user_id")
            ->where('level', GroupUserLevel::Owner)
            ->select(["name"]);
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
        $this->attributes['slug'] = Str::slug($value);
    }

    public function getLogoUrlAttribute()
    {
        return (is_null($this->logo)) ? null : Storage::url('avatars/'.$this->logo);
    }

    public function isMember(User $user): bool
    {
        return $this->users->contains($user);
    }

    public function isAdmin(User $user)
    {
        return $this->users->contains($user,
            fn($user) => $user->pivot->level === 'admin' || $user->pivot->level === 'owner');
    }
}
