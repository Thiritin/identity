<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class UserinfoResource extends JsonResource
{
    private $user;

    public static $wrap = null;

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [];

        $data['sub'] = $this->hashid;
        $this->loadMissing('groups');

        if ($this->scopeCheck('email')) {
            $data['email'] = $this->email;
            $data['email_verified'] = !is_null($this->email_verified_at);
        }

        if ($this->scopeCheck('profile')) {
            $data['name'] = $this->name;

            $data['avatar'] = ($request->user()->profile_photo_path) ? Storage::disk('s3-avatars')->url($request->user()->profile_photo_path) : null;
        }
        if ($this->whenLoaded('groups') && $this->scopeCheck('groups')) {
            $data['groups'] = $this->groups->pluck('hashid');
        }
        /**
         * APP Specific: NEXTCLOUD
         */
        if ($this->scopeCheck('nextcloud')) {
            $data['nextcloud_groups'] = $this->groups->whereNotNull('nextcloud_folder_name')->pluck('nextcloud_folder_name');
            $data['nextcloud_admin'] = $this->groups->contains('system_name', 'nextcloud_admins');
        }

        return $data;
    }
}
