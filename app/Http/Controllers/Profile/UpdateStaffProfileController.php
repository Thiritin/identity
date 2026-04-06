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

        // Sync skills
        if ($request->has('skills')) {
            $skillIds = collect($request->input('skills', []))
                ->map(fn (string $name) => \App\Models\Skill::firstOrCreate(
                    ['name' => str($name)->trim()->title()->toString()]
                ))
                ->pluck('id');

            $user->skills()->sync($skillIds);
        }

        return Redirect::route('settings.profile');
    }
}
