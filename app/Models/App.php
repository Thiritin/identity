<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
