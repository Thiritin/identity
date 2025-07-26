<?php

namespace Database\Seeders;

use App\Domains\User\Models\App;
use App\Domains\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AppSeeder extends Seeder
{
    /**
     * This seeder makes sure that we always have two apps in the system.
     * One for the Client Application and one for the Admin Application.
     */
    public function run()
    {
        $this->createApp('portal', 'Eurofurence Portal');
        $this->createApp('admin', 'Eurofurence Admin');
        $this->createApp('staff', 'Eurofurence StaffNet');
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
            'data' => [
                'client_name' => $clientName,
                'redirect_uris' => [
                    route('login.apps.callback', ['app' => $systemName]),
                ],
                'scope' => explode(' ', config('services.apps')[$systemName]['scopes']),
                'token_endpoint_auth_method' => 'client_secret_post',
                'frontchannel_logout_uri' => route('login.apps.frontchannel-logout', ['app' => $systemName]),
            ],
        ]);
        // Check if App was just created
        if ($app->wasRecentlyCreated && app()->isLocal()) {
            $app->fresh();
            $client_id = $app->data['client_id'];
            $client_secret = $app->data['client_secret'];

            $envName = Str::upper($systemName);
            shell_exec('sed -i "s/.*IDENTITY_' . $envName . '_ID=.*/IDENTITY_' . $envName . "_ID=$client_id/\" .env");
            shell_exec('sed -i "s/.*IDENTITY_' . $envName . '_SECRET=.*/IDENTITY_' . $envName . "_SECRET=$client_secret/\" .env");
        }
    }
}
