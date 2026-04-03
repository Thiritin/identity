<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Convention extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'convention_attendee')
            ->withPivot('is_staff')
            ->withTimestamps();
    }
}
