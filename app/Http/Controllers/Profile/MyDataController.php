<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\Hydra\Client as HydraClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

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
            'activityLog' => $this->getActivityLog($user),
            'connectedApps' => $this->getConnectedApps($hydra, $user),
        ];

        if ($isStaff) {
            $data['profile'] = array_merge($profile, [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'birthdate' => $user->birthdate?->format('Y-m-d'),
                'phone' => $user->phone,
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

    private function getActivityLog(mixed $user): mixed
    {
        return Activity::query()
            ->where('subject_type', $user->getMorphClass())
            ->where('subject_id', $user->id)
            ->with('causer')
            ->latest()
            ->paginate(20)
            ->through(fn (Activity $activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'properties' => $activity->properties,
                'causerName' => $activity->causer?->name,
                'causedBySelf' => $activity->causer_id === $user->id,
                'createdAt' => $activity->created_at->toISOString(),
            ]);
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

        return collect($sessions)->map(function ($session) use ($apps) {
            $clientId = $session['consent_request']['client']['client_id'] ?? null;
            $app = $apps->get($clientId);

            if (! $app) {
                return null;
            }

            return [
                'clientId' => $clientId,
                'name' => $app->name,
                'description' => $app->description,
                'icon' => $app->icon,
                'policyUri' => $app->data['policy_uri'] ?? null,
                'tosUri' => $app->data['tos_uri'] ?? null,
                'scopes' => $session['grant_scope'] ?? [],
                'consentedAt' => $session['handled_at'] ?? null,
            ];
        })->filter()->values()->all();
    }
}
