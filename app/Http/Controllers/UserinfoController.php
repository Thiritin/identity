<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserinfoResource;
use Illuminate\Http\Request;

class UserinfoController extends Controller
{
    public function __invoke(Request $request)
    {
        return new UserinfoResource($request->user());
    }
}
