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
}
