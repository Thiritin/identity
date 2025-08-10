<?php

namespace App\Http\Resources\V1;

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
        $hasFullStaffDetails = $request->user() && $request->user()->scopeCheck('view_full_staff_details');

        return [
            'user_id' => $this->hashid,
            'group_id' => $this->pivot->group->hashid,
            'name' => $this->name,
            'email' => $hasFullStaffDetails ? $this->email : null,
            'avatar' => $this->profile_photo_path,
            'level' => $this->pivot->level,
        ];
    }
}
