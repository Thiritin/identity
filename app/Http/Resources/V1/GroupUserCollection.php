<?php

namespace App\Http\Resources\V1;

use App\Models\GroupUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see GroupUser */
class GroupUserCollection extends ResourceCollection
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($user) use ($request) {
                $hasFullStaffDetails = $request->user() && $request->user()->scopeCheck('view_full_staff_details');

                return [
                    'user_id' => $user->hashid,
                    'group_id' => $user->pivot->group->hashid,
                    'name' => $user->name,
                    'email' => $hasFullStaffDetails ? $user->email : null,
                    'avatar' => $user->profile_photo_path,
                    'level' => $user->pivot->can_manage_members ? 'admin' : 'member',
                    'title' => $user->pivot->title,
                ];
            }),
        ];
    }
}
