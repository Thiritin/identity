<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppSeeder extends Seeder
{
    /**
     * Ensures the single first-party "Identity" OAuth client exists.
     * The Identity app is this Laravel application itself (portal + admin
     * panel), registered with Hydra as its own OIDC client.
     */
    public function run()
    {
        // Clean up legacy split-app rows from the portal/admin/staff era.
        // Iterate per-model (not ->delete() on the query builder) so
        // AppObserver::deleted fires for each row and the corresponding
        // Hydra client is removed. A mass delete would skip observers
        // and leave orphan clients in Hydra.
        App::whereIn('system_name', ['portal', 'admin', 'staff'])
            ->get()
            ->each(fn (App $app) => $app->delete());

        $this->createApp('identity', 'Eurofurence Identity');
    }

    public function createApp(
        string $systemName,
        string $clientName
    ) {
        $app = App::firstOrCreate([
            'system_name' => $systemName,
        ], [
            'name' => $clientName,
            'public' => false,
            'description' => 'This is the official ' . $clientName . '.',
            'icon' => 'CogsDuotone',
            'system_name' => $systemName,
            'user_id' => User::first()->id,
            'skip_consent' => true,
            'approved' => true,
            'first_party' => true,
            'data' => [
                'client_name' => $clientName,
                'redirect_uris' => [
                    route('login.callback'),
                ],
                'scope' => explode(' ', config('services.apps')[$systemName]['scopes']),
                'token_endpoint_auth_method' => 'client_secret_post',
                'frontchannel_logout_uri' => route('login.frontchannel-logout'),
            ],
        ]);

        if (! $app->wasRecentlyCreated) {
            $app->update([
                'skip_consent' => true,
                'approved' => true,
                'first_party' => true,
            ]);
        }

        // Check if App was just created
        if ($app->wasRecentlyCreated && app()->isLocal()) {
            $app->fresh();
            $client_id = $app->data['client_id'];
            $client_secret = $app->data['client_secret'];

            shell_exec('sed -i "s/.*IDENTITY_CLIENT_ID=.*/IDENTITY_CLIENT_ID=' . $client_id . '/" .env');
            shell_exec('sed -i "s/.*IDENTITY_CLIENT_SECRET=.*/IDENTITY_CLIENT_SECRET=' . $client_secret . '/" .env');
        }
    }
}
