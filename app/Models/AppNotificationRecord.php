<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotificationRecord extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    public $timestamps = false;

    protected $fillable = [
        'app_id',
        'notification_type_id',
        'user_id',
        'subject',
        'body',
        'cta_label',
        'cta_url',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
