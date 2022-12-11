<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserinfoResource extends JsonResource
{
    private $user;

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
        if ($this->scopeCheck('email')) {
            $data['email'] = $this->email;
            $data['email_verified'] = !is_null($this->email_verified_at);
        }
        if ($this->scopeCheck('profile')) {
            $data['name'] = $this->name;
            $data['avatar'] = $this->profile_photo_path;
        }
        if ($this->whenLoaded('groups') && $this->scopeCheck('groups')) {
            $data['groups'] = $this->groups->pluck('hashid');
        }

        return $data;
    }
}