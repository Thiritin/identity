<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ConventionAttendee extends Pivot
{
    protected $table = 'convention_attendee';

    protected function casts(): array
    {
        return [
            'is_attended' => 'boolean',
            'is_staff' => 'boolean',
        ];
    }
}
