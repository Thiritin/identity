<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request)
    {
        $user = $request->user();
        if ($user->name !== $request->get('name')) {
            $this->validate($request, ["unique:users,name"]);
            $user->name = $request->get('name');
            $user->save();
        }

        if ($user->email !== $request->get('email')) {
            $this->validate($request, ["unique:users,email"]);
            $user->changeMail($request->get('email'));
        }

    }
}
