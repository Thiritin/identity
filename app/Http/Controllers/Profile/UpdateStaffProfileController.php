<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateStaffProfileRequest;
use Illuminate\Support\Facades\Redirect;

class UpdateStaffProfileController extends Controller
{
    public function __invoke(UpdateStaffProfileRequest $request)
    {
        $user = $request->user();

        $validated = $request->validated();

        $data = collect($validated)->except('visibility')->toArray();

        if (isset($validated['visibility'])) {
            $data['staff_profile_visibility'] = $validated['visibility'];
        }

        $user->update($data);

        return Redirect::route('settings.profile');
    }
}
