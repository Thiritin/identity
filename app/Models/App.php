<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class App extends Model
{
    use HasFactory;

    protected $guarded = ['client_secret'];

    protected $casts = [
        'data' => 'array',
        'pinned' => 'boolean',
        'skip_consent' => 'boolean',
        'approved' => 'boolean',
        'first_party' => 'boolean',
        'allow_notifications' => 'boolean',
        'webhook_subscribed_fields' => 'array',
        'webhook_secret' => 'encrypted',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AppCategory::class, 'category_id');
    }

    public function notificationTypes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NotificationType::class);
    }

    public function webhookDeliveries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function isApproved(): bool
    {
        return (bool) $this->approved;
    }

    public function isFirstParty(): bool
    {
        return (bool) $this->first_party;
    }
}
