<?php

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class WebauthnCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'credential_id',
        'public_key',
        'attestation_object',
        'aaguid',
        'sign_count',
        'name',
        'type',
        'transports',
        'last_used_at',
    ];

    protected $casts = [
        'transports' => 'array',
        'last_used_at' => 'datetime',
        'sign_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsUsed(): void
    {
        $this->increment('sign_count');
        $this->update(['last_used_at' => now()]);
    }

    public function getDeviceNameAttribute(): string
    {
        return $this->name ?: 'Security Key';
    }

    public function getLastUsedHumanAttribute(): string
    {
        if (!$this->last_used_at) {
            return 'Never used';
        }

        return $this->last_used_at->diffForHumans();
    }

    public function getTransportTypesAttribute(): array
    {
        return $this->transports ?? [];
    }
}