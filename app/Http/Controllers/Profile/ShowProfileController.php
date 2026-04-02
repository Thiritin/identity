<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Support\EurofurenceEdition;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ShowProfileController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();
        $isStaff = $user->isStaff();

        $staffProfile = null;
        $groupMemberships = null;
        $eurofurenceEditions = null;

        if ($isStaff) {
            $staffProfile = [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'birthdate' => $user->birthdate?->format('Y-m-d'),
                'phone' => $user->phone,
                'telegram_id' => $user->telegram_id,
                'telegram_username' => $user->telegram_username,
                'spoken_languages' => $user->spoken_languages ?? [],
                'credit_as' => $user->credit_as,
                'first_eurofurence' => $user->first_eurofurence,
                'first_year_staff' => $user->first_year_staff,
                'visibility' => $user->staff_profile_visibility ?? [],
            ];

            $groupMemberships = $user->groups()
                ->where(fn ($q) => $q->whereNull('system_name')->orWhere('system_name', '!=', 'staff'))
                ->get()
                ->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'title' => $group->pivot->title,
                    'level' => $group->pivot->level->value,
                    'credit_as' => $group->pivot->credit_as,
                ]);

            $eurofurenceEditions = EurofurenceEdition::allEditions();
        }

        return Inertia::render('Settings/Profile', [
            'staffProfile' => $staffProfile,
            'groupMemberships' => $groupMemberships,
            'eurofurenceEditions' => $eurofurenceEditions,
        ]);
    }
}
