<?php

namespace App\Http\Resources\V2;

use App\Models\Convention;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Convention */
class ConventionResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'year' => $this->year,
            'theme' => $this->theme,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'location' => $this->location,
            'website_url' => $this->website_url,
            'conbook_url' => $this->conbook_url,
            'attendees_count' => $this->attendees_count,
            'background_image_url' => $this->background_image_url,
            'dailies' => $this->dailies ?? [],
            'videos' => $this->videos ?? [],
            'photos' => $this->photos ?? [],
        ];
    }
}
