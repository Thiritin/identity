<?php

namespace App\Http\Resources\V2;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class GroupMemberResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $hasFullStaffDetails = $request->user() && $request->user()->scopeCheck('view_full_staff_details');

        return [
            'user_id' => $this->hashid,
            'name' => $this->name,
            'email' => $hasFullStaffDetails ? $this->email : null,
            'avatar' => $this->profile_photo_path
                ? Storage::disk('s3-avatars')->url($this->profile_photo_path)
                : null,
            'level' => $this->pivot->level->value,
            'title' => $this->pivot->title,
            'credit_as' => $this->pivot->credit_as,
        ];
    }
}
