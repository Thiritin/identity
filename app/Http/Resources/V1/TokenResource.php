<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class TokenResource extends JsonResource
{
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
        return $this->resource;
    }
}
