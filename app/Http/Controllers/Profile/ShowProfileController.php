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
        $hasConsent = $isStaff && $user->hasStaffProfileConsent();

        $staffProfile = null;
        $groupMemberships = null;

        if ($isStaff) {
            $staffProfile = [
                'firstname' => $hasConsent ? $user->firstname : null,
                'lastname' => $hasConsent ? $user->lastname : null,
                'pronouns' => $hasConsent ? $user->pronouns : null,
                'birthdate' => $hasConsent ? $user->birthdate?->format('Y-m-d') : null,
                'phone' => $hasConsent ? $user->phone : null,
                'address_line1' => $hasConsent ? $user->address_line1 : null,
                'address_line2' => $hasConsent ? $user->address_line2 : null,
                'city' => $hasConsent ? $user->city : null,
                'postal_code' => $hasConsent ? $user->postal_code : null,
                'country' => $hasConsent ? $user->country : null,
                'emergency_contact_name' => $hasConsent ? $user->emergency_contact_name : null,
                'emergency_contact_phone' => $hasConsent ? $user->emergency_contact_phone : null,
                'emergency_contact_telegram' => $hasConsent ? $user->emergency_contact_telegram : null,
                'spoken_languages' => $hasConsent ? ($user->spoken_languages ?? []) : [],
                'credit_as' => $hasConsent ? $user->credit_as : null,
                'visibility' => $hasConsent ? ($user->staff_profile_visibility ?? []) : [],
                'consent' => [
                    'granted' => $hasConsent,
                    'granted_at' => $user->staff_profile_consent_at?->toIso8601String(),
                    'version' => $user->staff_profile_consent_version,
                    'current_version' => \App\Support\StaffProfile\ConsentNotice::CURRENT_VERSION,
                    'is_current' => $user->hasCurrentStaffProfileConsent(),
                ],
            ];

            if ($hasConsent) {
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
            } else {
                $groupMemberships = collect();
            }
        }

        if ($isStaff && ! $hasConsent) {
            $conventionAttendance = collect();
        } else {
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
        }

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
