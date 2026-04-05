<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\StoreAppRequest;
use App\Http\Requests\Developer\UpdateAppGeneralRequest;
use App\Http\Requests\Developer\UpdateAppLogoutRequest;
use App\Http\Requests\Developer\UpdateAppOAuthRequest;
use App\Models\App;
use App\Services\Hydra\Client;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AppsController extends Controller
{
    /**
     * Scopes that developers are not allowed to request for their apps.
     */
    public const array RESTRICTED_SCOPES = [
        'registration.reg.test',
        'registration.reg.live',
        'registration.room.test',
        'registration.room.live',
        'view_full_staff_details',
    ];

    public function index()
    {
        $user = auth()->user();
        $isDeveloper = (bool) $user->is_developer;

        $apps = $isDeveloper
            ? $user->apps()
                ->select('id', 'client_id', 'data', 'approved', 'created_at')
                ->latest()
                ->get()
                ->map(fn (App $app) => [
                    'id' => $app->id,
                    'client_id' => $app->client_id,
                    'client_name' => $app->data['client_name'] ?? '',
                    'approved' => $app->isApproved(),
                    'created_at' => $app->created_at->toDateTimeString(),
                ])
            : [];

        return Inertia::render('Developers', [
            'apps' => $apps,
            'isDeveloper' => $isDeveloper,
        ]);
    }

    public function create()
    {
        $scopes = $this->filterRestrictedScopes(app(Client::class)->getScopes() ?? []);

        return Inertia::render('Settings/Apps/AppCreate', [
            'availableScopes' => $scopes,
            'isStaff' => auth()->user()->isStaff(),
        ]);
    }

    public function store(StoreAppRequest $request)
    {
        $validated = $request->validated();
        $firstParty = (bool) auth()->user()->isStaff();

        $data = [
            'client_name' => $validated['client_name'],
            'redirect_uris' => [$validated['redirect_uri']],
            'post_logout_redirect_uris' => [],
            'scope' => $validated['scope'] ?? ['openid'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'client_secret_post',
            'subject_type' => 'public',
        ];

        try {
            $app = auth()->user()->apps()->create([
                'data' => $data,
                'name' => $validated['client_name'],
                'description' => '',
                'first_party' => $firstParty,
            ]);
        } catch (\Exception $e) {
            if (isset($app) && ! $app->client_id) {
                $app->deleteQuietly();
            }

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Failed to create app. Please try again.',
            ]);

            return redirect()->route('developers.index');
        }

        $app->refresh();
        $rawSecret = $app->data['client_secret'] ?? null;

        $cleanData = $app->data;
        unset($cleanData['client_secret']);
        $app->data = $cleanData;
        $app->saveQuietly();

        return redirect()->route('developers.credentials', $app)->with('clientSecret', $rawSecret);
    }

    public function general(App $app)
    {
        Gate::authorize('update', $app);

        return Inertia::render('Settings/Apps/AppDetail/General', [
            'app' => $this->formatApp($app),
        ]);
    }

    public function updateGeneral(UpdateAppGeneralRequest $request, App $app)
    {
        Gate::authorize('update', $app);

        $validated = $request->validated();

        $data = array_merge($app->data, [
            'client_name' => $validated['client_name'],
        ]);

        $updates = [
            'data' => $data,
            'name' => $validated['client_name'],
            'description' => $validated['description'] ?? $app->description,
            'url' => $validated['app_url'] ?? $app->url,
        ];

        if (! $app->isFirstParty()) {
            $updates['developer_name'] = $validated['developer_name'] ?? $app->developer_name;
            $updates['privacy_policy_url'] = $validated['privacy_policy_url'] ?? $app->privacy_policy_url;
            $updates['terms_of_service_url'] = $validated['terms_of_service_url'] ?? $app->terms_of_service_url;
        }

        if ($request->hasFile('icon')) {
            $updates['image'] = $request->file('icon')->store('app-icons', 'public');
        }

        $app->update($updates);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('apps_updated'),
        ]);

        return redirect()->route('developers.general', $app);
    }

    public function oauth(App $app)
    {
        Gate::authorize('update', $app);

        $scopes = $this->filterRestrictedScopes(app(Client::class)->getScopes() ?? []);

        return Inertia::render('Settings/Apps/AppDetail/OAuth', [
            'app' => $this->formatApp($app),
            'availableScopes' => $scopes,
        ]);
    }

    public function updateOAuth(UpdateAppOAuthRequest $request, App $app)
    {
        Gate::authorize('update', $app);

        $validated = $request->validated();

        $data = array_merge($app->data, [
            'redirect_uris' => $validated['redirect_uris'],
            'scope' => $validated['scope'] ?? ['openid'],
        ]);

        $app->update(['data' => $data]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('apps_updated'),
        ]);

        return redirect()->route('developers.oauth', $app);
    }

    public function logout(App $app)
    {
        Gate::authorize('update', $app);

        return Inertia::render('Settings/Apps/AppDetail/Logout', [
            'app' => $this->formatApp($app),
        ]);
    }

    public function updateLogout(UpdateAppLogoutRequest $request, App $app)
    {
        Gate::authorize('update', $app);

        $validated = $request->validated();

        $data = array_merge($app->data, [
            'post_logout_redirect_uris' => $validated['post_logout_redirect_uris'] ?? [],
            'frontchannel_logout_uri' => $validated['frontchannel_logout_uri'] ?? null,
            'backchannel_logout_uri' => $validated['backchannel_logout_uri'] ?? null,
        ]);

        $app->update(['data' => $data]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('apps_updated'),
        ]);

        return redirect()->route('developers.logout', $app);
    }

    public function credentials(App $app)
    {
        Gate::authorize('update', $app);

        return Inertia::render('Settings/Apps/AppDetail/Credentials', [
            'app' => $this->formatApp($app),
        ]);
    }

    public function danger(App $app)
    {
        Gate::authorize('update', $app);

        return Inertia::render('Settings/Apps/AppDetail/Danger', [
            'app' => $this->formatApp($app),
        ]);
    }

    public function destroy(App $app)
    {
        Gate::authorize('delete', $app);

        $app->delete();

        return redirect()->route('developers.index');
    }

    public function regenerateSecret(App $app)
    {
        Gate::authorize('regenerateSecret', $app);

        $newRawSecret = bin2hex(random_bytes(32));

        $hydraApp = \App\Services\Hydra\Models\App::find($app->client_id);
        $hydraApp->update(array_merge($app->data, [
            'client_secret' => $newRawSecret,
        ]));

        $app->client_secret = Hash::make($newRawSecret);
        $cleanData = $app->data;
        unset($cleanData['client_secret']);
        $app->data = $cleanData;
        $app->saveQuietly();

        return redirect()->route('developers.credentials', $app)->with('clientSecret', $newRawSecret);
    }

    /**
     * @param  array<int, string>  $scopes
     * @return array<int, string>
     */
    private function filterRestrictedScopes(array $scopes): array
    {
        return array_values(array_filter(
            $scopes,
            fn (string $scope) => ! in_array($scope, self::RESTRICTED_SCOPES, true),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatApp(App $app): array
    {
        return [
            'id' => $app->id,
            'client_id' => $app->client_id,
            'client_name' => $app->data['client_name'] ?? '',
            'redirect_uris' => $app->data['redirect_uris'] ?? [],
            'post_logout_redirect_uris' => $app->data['post_logout_redirect_uris'] ?? [],
            'frontchannel_logout_uri' => $app->data['frontchannel_logout_uri'] ?? '',
            'backchannel_logout_uri' => $app->data['backchannel_logout_uri'] ?? '',
            'scope' => $app->data['scope'] ?? [],
            'approved' => $app->isApproved(),
            'first_party' => $app->isFirstParty(),
            'description' => $app->description ?? '',
            'app_url' => $app->url ?? '',
            'developer_name' => $app->developer_name ?? '',
            'privacy_policy_url' => $app->privacy_policy_url ?? '',
            'terms_of_service_url' => $app->terms_of_service_url ?? '',
            'icon_url' => $app->image ? asset('storage/' . $app->image) : null,
            'created_at' => $app->created_at->toDateTimeString(),
        ];
    }
}
