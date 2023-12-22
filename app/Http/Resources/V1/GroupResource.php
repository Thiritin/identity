<?php

namespace App\Http\Resources\V1;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Group */
class GroupResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->hashid,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'logo' => $this->logo,
            'slug' => $this->slug,
            'translations' => $this->translations,
            'users_count' => $this->users_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
