<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\UserinfoResource;
use Illuminate\Http\Request;

class UserinfoController extends Controller
{
    public function __invoke(Request $request)
    {
        return new UserinfoResource($request->user());
    }
}
