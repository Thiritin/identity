<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Convention;
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

        if ($isStaff) {
            $staffProfile = [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'birthdate' => $user->birthdate?->format('Y-m-d'),
                'phone' => $user->phone,
                'spoken_languages' => $user->spoken_languages ?? [],
                'credit_as' => $user->credit_as,
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
        }

        $conventionAttendance = $user->conventions()
            ->orderByDesc('year')
            ->get()
            ->map(fn ($convention) => [
                'id' => $convention->id,
                'name' => $convention->name,
                'year' => $convention->year,
                'is_attended' => (bool) $convention->pivot->is_attended,
                'is_staff' => (bool) $convention->pivot->is_staff,
            ]);

        $allConventions = Convention::query()->orderBy('year')->get(['id', 'name', 'year']);

        return Inertia::render('Settings/Profile', [
            'staffProfile' => $staffProfile,
            'groupMemberships' => $groupMemberships,
            'conventionAttendance' => $conventionAttendance,
            'allConventions' => $allConventions,
            'telegram' => [
                'linked' => $user->telegram_id !== null,
                'username' => $user->telegram_username,
            ],
        ]);
    }
}
