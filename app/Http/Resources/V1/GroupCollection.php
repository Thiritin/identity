<?php

namespace App\Http\Resources\V1;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see Group */
class GroupCollection extends ResourceCollection
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
        ];
    }
}
