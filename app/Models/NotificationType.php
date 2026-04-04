<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'key',
        'name',
        'description',
        'category',
        'default_channels',
        'disabled',
    ];

    protected $casts = [
        'category' => NotificationCategory::class,
        'default_channels' => 'array',
        'disabled' => 'boolean',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
