<?php

namespace App\Models;

use App\Enums\TwoFactorTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TwoFactor extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'identifier',
        'secret',
        'credential_id',
        'public_key',
        'sign_count',
        'transports',
        'aaguid',
        'last_used_at',
        'codes',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'type' => TwoFactorTypeEnum::class,
        'secret' => 'encrypted',
        'public_key' => 'encrypted',
        'transports' => 'array',
        'sign_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
