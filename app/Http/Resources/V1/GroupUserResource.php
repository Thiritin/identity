<?php

namespace App\Http\Resources\V1;

use App\Domains\Staff\Models\GroupUser;
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
            'group_id' => $this->pivot->group->hashid,
            'user_id' => $this->pivot->user->hashid,
            'level' => $this->pivot->level,
        ];
    }
}
