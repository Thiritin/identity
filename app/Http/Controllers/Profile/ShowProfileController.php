<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Convention;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Locale;
use ResourceBundle;

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
                'pronouns' => $user->pronouns,
                'birthdate' => $user->birthdate?->format('Y-m-d'),
                'phone' => $user->phone,
                'address_line1' => $user->address_line1,
                'address_line2' => $user->address_line2,
                'city' => $user->city,
                'postal_code' => $user->postal_code,
                'country' => $user->country,
                'emergency_contact_name' => $user->emergency_contact_name,
                'emergency_contact_phone' => $user->emergency_contact_phone,
                'emergency_contact_telegram' => $user->emergency_contact_telegram,
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

        $availableLanguages = collect(ResourceBundle::getLocales(''))
            ->filter(fn (string $loc) => strlen($loc) === 2)
            ->map(fn (string $loc) => [
                'code' => $loc,
                'name' => ucfirst(Locale::getDisplayLanguage($loc, $loc)),
            ])
            ->filter(fn (array $lang) => $lang['name'] !== $lang['code'])
            ->sortBy('name')
            ->values();

        return Inertia::render('Settings/Profile', [
            'staffProfile' => $staffProfile,
            'staffProfileVisibilityDefaults' => \App\Models\User::staffFieldDefaultVisibility(),
            'groupMemberships' => $groupMemberships,
            'conventionAttendance' => $conventionAttendance,
            'allConventions' => $allConventions,
            'availableLanguages' => $availableLanguages,
            'telegram' => [
                'linked' => $user->telegram_id !== null,
                'username' => $user->telegram_username,
            ],
        ]);
    }
}
