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
        'start_date',
        'end_date',
        'theme',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'convention_attendee')
            ->withPivot('is_attended', 'is_staff')
            ->withTimestamps();
    }
}
