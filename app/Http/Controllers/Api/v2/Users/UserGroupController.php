<?php

namespace App\Http\Controllers\Api\v2\Users;

use App\Http\Controllers\Api\v2\Concerns\ChecksScopes;
use App\Http\Controllers\Controller;
use App\Http\Resources\V2\UserGroupResource;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{
    use ChecksScopes;

    public function index(Request $request, string $user)
    {
        $this->requireScope('Groups.Read');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            $this->requireScope('Groups.Read.All');
        }

        $target->load('groups');

        return response()->json(
            UserGroupResource::collection($target->groups)->toArray($request),
        );
    }
}
