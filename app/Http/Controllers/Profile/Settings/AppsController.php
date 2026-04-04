<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreAppRequest;
use App\Http\Requests\Staff\UpdateAppRequest;
use App\Models\App;
use App\Services\Hydra\Client;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AppsController extends Controller
{
    public function index()
    {
        $apps = auth()->user()->apps()
            ->select('id', 'client_id', 'data', 'approved', 'created_at')
            ->latest()
            ->get()
            ->map(fn (App $app) => [
                'id' => $app->id,
                'client_id' => $app->client_id,
                'client_name' => $app->data['client_name'] ?? '',
                'approved' => $app->isApproved(),
                'created_at' => $app->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Developers', [
            'apps' => $apps,
        ]);
    }

    public function create()
    {
        $scopes = app(Client::class)->getScopes() ?? [];

        return Inertia::render('Settings/Apps/AppCreate', [
            'availableScopes' => $scopes,
        ]);
    }

    public function store(StoreAppRequest $request)
    {
        $validated = $request->validated();

        $data = [
            'client_name' => $validated['client_name'],
            'redirect_uris' => $validated['redirect_uris'],
            'post_logout_redirect_uris' => $validated['post_logout_redirect_uris'] ?? [],
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

        return Inertia::render('Settings/Apps/AppShow', [
            'app' => $this->formatApp($app),
            'clientSecret' => $rawSecret,
        ]);
    }

    public function show(App $app)
    {
        Gate::authorize('view', $app);

        return Inertia::render('Settings/Apps/AppShow', [
            'app' => $this->formatApp($app),
            'clientSecret' => null,
        ]);
    }

    public function edit(App $app)
    {
        Gate::authorize('update', $app);

        $scopes = app(Client::class)->getScopes() ?? [];

        return Inertia::render('Settings/Apps/AppEdit', [
            'app' => $this->formatApp($app),
            'availableScopes' => $scopes,
        ]);
    }

    public function update(UpdateAppRequest $request, App $app)
    {
        Gate::authorize('update', $app);

        $validated = $request->validated();

        $data = array_merge($app->data, [
            'client_name' => $validated['client_name'],
            'redirect_uris' => $validated['redirect_uris'],
            'post_logout_redirect_uris' => $validated['post_logout_redirect_uris'] ?? [],
            'scope' => $validated['scope'] ?? ['openid'],
        ]);

        $app->update([
            'data' => $data,
            'name' => $validated['client_name'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('apps_updated'),
        ]);

        return redirect()->route('developers.edit', $app);
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

        $scopes = app(Client::class)->getScopes() ?? [];

        return Inertia::render('Settings/Apps/AppEdit', [
            'app' => $this->formatApp($app),
            'availableScopes' => $scopes,
            'clientSecret' => $newRawSecret,
        ]);
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
            'scope' => $app->data['scope'] ?? [],
            'approved' => $app->isApproved(),
            'created_at' => $app->created_at->toDateTimeString(),
        ];
    }
}
