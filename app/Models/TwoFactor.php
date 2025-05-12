<?php

namespace App\Models;

use App\Enums\TwoFactorTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property string|null $identifier
 * @property TwoFactorTypeEnum $type
 * @property mixed|null $secret
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactor withoutTrashed()
 * @mixin \Eloquent
 */
class TwoFactor extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'last_used_at' => 'datetime',
        'type' => TwoFactorTypeEnum::class,
        'secret' => 'encrypted',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
