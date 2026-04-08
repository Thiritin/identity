<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConventionAttendee extends Pivot
{
    protected $table = 'convention_attendee';

    public $incrementing = true;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_attended' => 'boolean',
            'is_staff' => 'boolean',
        ];
    }

    public function convention(): BelongsTo
    {
        return $this->belongsTo(Convention::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
