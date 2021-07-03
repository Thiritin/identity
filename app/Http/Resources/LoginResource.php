<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    private $link;

    public function __construct($link)
    {
        $this->link = $link;
    }

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'redirect_to' => $this->link,
        ];
    }
}
