<?php

namespace App\Http\Resources\V1;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Group */
class GroupResource extends JsonResource
{
    /**
     * @param  Request  $request
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
            'users' => $this->when($this->relationLoaded('users'), function () use ($request) {
                return $this->users->map(function ($user) use ($request) {
                    $hasFullStaffDetails = $request->user() && $request->user()->tokenCan('view_full_staff_details');

                    return [
                        'user_id' => $user->hashid,
                        'username' => $user->name,
                        'email' => $hasFullStaffDetails ? $user->email : null,
                        'level' => $user->pivot->level,
                        'avatar' => $user->profile_photo_path ? Storage::disk('s3-avatars')->url($user->profile_photo_path) : null,
                    ];
                });
            }),
        ];
    }
}
