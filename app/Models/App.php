<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $system_name
 * @property string|null $client_id
 * @property string|null $client_secret
 * @property string|null $name
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $url
 * @property string|null $starts_at
 * @property string|null $ends_at
 * @property int $featured
 * @property int $public
 * @property int $priority
 * @property int $user_id
 * @property array<array-key, mixed>|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Group> $groups
 * @property-read int|null $groups_count
 * @property-read \App\Models\User $owner
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App wherePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereSystemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereUserId($value)
 *
 * @mixin \Eloquent
 */
class App extends Model
{
    protected $guarded = ['client_secret'];

    protected $casts = [
        'data' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }
}
