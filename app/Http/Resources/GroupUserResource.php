<?php

namespace App\Http\Resources;

use App\Models\GroupUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GroupUser */
class GroupUserResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'group_id' => $this->pivot->group_id,
            'user_id' => $this->pivot->user_id,
            'level' => $this->pivot->level,
        ];
    }
}
