<?php

namespace App\Http\Resources\V2;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class UserinfoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $data = [];

        $data['sub'] = $this->hashid;
        $this->loadMissing('groups');

        if ($this->scopeCheck('email')) {
            $data['email'] = $this->email;
            $data['email_verified'] = ! is_null($this->email_verified_at);
        }

        if ($this->scopeCheck('profile')) {
            $data['name'] = $this->name;
            $data['avatar'] = $this->profile_photo_path
                ? Storage::disk('s3-avatars')->url($this->profile_photo_path)
                : null;
        }

        if ($this->relationLoaded('groups') && $this->scopeCheck('groups')) {
            $data['groups'] = $this->groups->map(fn ($group) => [
                'id' => $group->hashid,
                'name' => $group->name,
                'type' => $group->type->value,
                'slug' => $group->slug,
                'level' => $group->pivot->level->value,
                'title' => $group->pivot->title,
            ])->values();
        }

        if ($this->scopeCheck('staff.my.read')) {
            $data['firstname'] = $this->firstname;
            $data['lastname'] = $this->lastname;
            $data['birthdate'] = $this->birthdate?->toDateString();
            $data['phone'] = $this->phone;
            $data['telegram_username'] = $this->telegram_username;
            $data['spoken_languages'] = $this->spoken_languages;
            $data['credit_as'] = $this->credit_as;
            $data['nda_checked_at'] = $this->nda_checked_at?->toIso8601String();
        }

        if ($this->scopeCheck('nextcloud')) {
            $data['nextcloud_groups'] = $this->groups->whereNotNull('nextcloud_folder_name')->pluck('nextcloud_folder_name');
            $data['nextcloud_admin'] = $this->groups->contains('system_name', 'nextcloud_admins');
        }

        return $data;
    }
}
