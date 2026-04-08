<?php

namespace App\Http\Controllers\Api\v2\Users;

use App\Http\Controllers\Api\v2\Concerns\ChecksScopes;
use App\Http\Controllers\Controller;
use App\Http\Resources\V2\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ChecksScopes;

    public function show(Request $request, string $user)
    {
        $this->requireScope('User.Read');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            $this->requireScope('User.Read.All');
        }

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));

            if (in_array('groups', $includes, true) && $this->hasScope('Groups.Read')) {
                $target->load('groups');
            }
        }

        return response()->json(
            (new UserResource($target))->toArray($request),
        );
    }
}
