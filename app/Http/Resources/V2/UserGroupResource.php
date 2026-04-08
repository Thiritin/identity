<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserGroupResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hashid,
            'name' => $this->name,
            'type' => $this->type->value,
            'slug' => $this->slug,
            'level' => $this->pivot->level->value,
            'title' => $this->pivot->title,
        ];
    }
}
