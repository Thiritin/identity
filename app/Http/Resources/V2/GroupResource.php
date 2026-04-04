<?php

namespace App\Http\Resources\V2;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Group */
class GroupResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hashid,
            'type' => $this->type->value,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'logo' => $this->logo_url,
            'parent_id' => $this->parent?->hashid,
            'parent' => new GroupResource($this->whenLoaded('parent')),
            'member_count' => $this->whenCounted('users'),
            'translations' => $this->translations,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'children' => GroupResource::collection($this->whenLoaded('children')),
            'members' => GroupMemberResource::collection($this->whenLoaded('users')),
        ];
    }
}
