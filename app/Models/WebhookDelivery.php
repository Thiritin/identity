<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'id', 'app_id', 'event', 'url', 'payload', 'signature', 'status', 'attempts',
        'response_code', 'response_body', 'error', 'next_retry_at', 'delivered_at', 'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'response_code' => 'integer',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
