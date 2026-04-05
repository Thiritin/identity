<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\Hydra\Client as HydraClient;
use App\Support\StaffProfile\ConsentNotice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MyDataController extends Controller
{
    public function __invoke(HydraClient $hydra): Response
    {
        $user = Auth::user();
        $isStaff = $user->isStaff();

        $profile = [
            'name' => $user->name,
            'email' => $user->email,
            'profilePhotoPath' => $user->profile_photo_path,
            'preferences' => $user->preferences,
        ];

        $data = [
            'profile' => $profile,
            'isStaff' => $isStaff,
            'connectedApps' => $this->getConnectedApps($hydra, $user),
            'staffProfileConsent' => [
                'granted'         => $user->hasStaffProfileConsent(),
                'granted_at'      => $user->staff_profile_consent_at?->toIso8601String(),
                'version'         => $user->staff_profile_consent_version,
                'current_version' => ConsentNotice::CURRENT_VERSION,
                'is_current'      => $user->hasCurrentStaffProfileConsent(),
                'is_staff'        => $user->isStaff(),
            ],
        ];

        if ($isStaff) {
            $data['profile'] = array_merge($profile, [
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
                'telegram' => $user->telegram_username,
                'spokenLanguages' => $user->spoken_languages,
                'creditAs' => $user->credit_as,
                'firstEurofurence' => $user->first_eurofurence,
                'firstYearStaff' => $user->first_year_staff,
            ]);

            $data['groups'] = $user->groups->map(fn ($group) => [
                'name' => $group->name,
                'level' => $group->pivot->level,
                'title' => $group->pivot->title,
                'creditAs' => $group->pivot->credit_as,
            ]);

            $data['conventions'] = $user->conventions->map(fn ($convention) => [
                'name' => $convention->name,
                'year' => $convention->year,
                'isStaff' => (bool) $convention->pivot->is_staff,
            ]);

            $data['visibility'] = $user->staff_profile_visibility;
        }

        return Inertia::render('Settings/MyData', $data);
    }

    private function getConnectedApps(HydraClient $hydra, mixed $user): ?array
    {
        try {
            $sessions = $hydra->getConsentSessions($user->hashid);
        } catch (\Exception $e) {
            Log::warning('Failed to load consent sessions for My Data page', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $clientIds = collect($sessions)->map(
            fn ($s) => $s['consent_request']['client']['client_id'] ?? null
        )->filter()->unique()->values();

        $apps = App::whereIn('client_id', $clientIds)->get()->keyBy('client_id');

        return collect($sessions)
            ->groupBy(fn ($s) => $s['consent_request']['client']['client_id'] ?? null)
            ->filter(fn ($group, $clientId) => $clientId && $apps->has($clientId))
            ->map(function ($group, $clientId) use ($apps) {
                $app = $apps->get($clientId);
                $latest = $group->sortByDesc('handled_at')->first();
                $allScopes = $group->flatMap(fn ($s) => $s['grant_scope'] ?? [])->unique()->values()->all();

                return [
                    'clientId' => $clientId,
                    'name' => $app->name,
                    'description' => $app->description,
                    'image' => $app->image,
                    'policyUri' => $app->data['policy_uri'] ?? null,
                    'tosUri' => $app->data['tos_uri'] ?? null,
                    'scopes' => $allScopes,
                    'consentedAt' => $latest['handled_at'] ?? null,
                ];
            })->values()->all();
    }
}
