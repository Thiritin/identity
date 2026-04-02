<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Support\Facades\Redirect;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request)
    {
        $user = $request->user();

        if ($user->name !== $request->get('name')) {
            activity()->by($user)->log('name.change');
            $user->update(['name' => $request->get('name')]);
        }

        return Redirect::route('settings.profile');
    }
}
