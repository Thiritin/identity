<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'convention_id' => $this->convention_id,
            'is_attended' => $this->is_attended,
            'is_staff' => $this->is_staff,
        ];

        if ($this->relationLoaded('convention')) {
            $data['convention'] = new ConventionResource($this->convention);
        }

        return $data;
    }
}
