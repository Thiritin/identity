<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthSession extends Model
{
    use HasFactory;
    use Prunable;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'client_ids',
        'authenticated_at',
        'last_seen_at',
    ];

    protected $casts = [
        'authenticated_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'client_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addClientId(string $clientId): void
    {
        $ids = $this->client_ids ?? [];

        if (! in_array($clientId, $ids)) {
            $ids[] = $clientId;
            $this->client_ids = $ids;
            $this->save();
        }
    }

    public function prunable(): Builder
    {
        return static::where('last_seen_at', '<', now()->subDays(30));
    }
}
