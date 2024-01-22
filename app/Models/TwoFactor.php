<?php

namespace App\Models;

use App\Enums\TwoFactorTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
