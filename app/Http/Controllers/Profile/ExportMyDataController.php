<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\Hydra\Client as HydraClient;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportMyDataController extends Controller
{
    public function __invoke(HydraClient $hydra): StreamedResponse
    {
        $user = Auth::user();

        $profile = [
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_path' => $user->profile_photo_path,
            'preferences' => $user->preferences,
            'created_at' => $user->created_at?->toISOString(),
        ];

        if ($user->isStaff()) {
            $profile = array_merge($profile, [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'birthdate' => $user->birthdate?->format('Y-m-d'),
                'phone' => $user->phone,
                'telegram_username' => $user->telegram_username,
                'spoken_languages' => $user->spoken_languages,
                'credit_as' => $user->credit_as,
                'first_eurofurence' => $user->first_eurofurence,
                'first_year_staff' => $user->first_year_staff,
                'staff_profile_visibility' => $user->staff_profile_visibility,
            ]);
        }

        $groups = $user->groups->map(fn ($g) => [
            'name' => $g->name,
            'level' => $g->pivot->level,
            'title' => $g->pivot->title,
        ])->all();

        $conventions = $user->conventions->map(fn ($c) => [
            'name' => $c->name,
            'year' => $c->year,
            'is_staff' => (bool) $c->pivot->is_staff,
        ])->all();

        $connectedApps = [];
        try {
            $sessions = $hydra->getConsentSessions($user->hashid);
            $clientIds = collect($sessions)->pluck('consent_request.client.client_id')->filter()->unique();
            $apps = App::whereIn('client_id', $clientIds)->get()->keyBy('client_id');

            $connectedApps = collect($sessions)->map(function ($s) use ($apps) {
                $clientId = $s['consent_request']['client']['client_id'] ?? null;
                $app = $apps->get($clientId);

                return [
                    'client_id' => $clientId,
                    'app_name' => $app?->name ?? $clientId,
                    'granted_scopes' => $s['grant_scope'] ?? [],
                    'consented_at' => $s['handled_at'] ?? null,
                ];
            })->all();
        } catch (\Exception) {
            $connectedApps = ['error' => 'Could not retrieve connected apps'];
        }

        $activityLog = Activity::query()
            ->where('subject_type', $user->getMorphClass())
            ->where('subject_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (Activity $a) => [
                'description' => $a->description,
                'properties' => $a->properties,
                'created_at' => $a->created_at->toISOString(),
            ])->all();

        $export = [
            'exported_at' => now()->toISOString(),
            'profile' => $profile,
            'groups' => $groups,
            'conventions' => $conventions,
            'connected_apps' => $connectedApps,
            'activity_log' => $activityLog,
        ];

        $filename = 'ef-identity-export-' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($export) {
            echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
}
