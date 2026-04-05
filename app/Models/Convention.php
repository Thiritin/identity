<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Convention extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
        'start_date',
        'end_date',
        'theme',
        'location',
        'website_url',
        'conbook_url',
        'attendees_count',
        'background_image_path',
        'dailies',
        'videos',
        'photos',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'attendees_count' => 'integer',
            'dailies' => 'array',
            'videos' => 'array',
            'photos' => 'array',
        ];
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'convention_attendee')
            ->using(ConventionAttendee::class)
            ->withPivot('is_attended', 'is_staff')
            ->withTimestamps();
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date');
    }

    public function getBackgroundImageUrlAttribute(): ?string
    {
        return $this->background_image_path
            ? Storage::disk('s3')->url($this->background_image_path)
            : null;
    }
}
