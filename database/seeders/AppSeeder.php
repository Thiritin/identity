<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppSeeder extends Seeder
{
    /**
     * This seeder makes sure that we always have two apps in the system.
     * One for the Client Application and one for the Admin Application.
     */
    public function run()
    {
        $app = App::firstOrCreate([
            "id" => 1,
        ], [
            "id" => 1,
            'user_id' => User::first()->id,
            'data' => [
                "client_name" => "Eurofurence IAM",
                "redirect_uris" => [
                    route('auth.oidc.callback'),
                ],
                "scope" => [
                    "openid",
                    "offline_access",
                    "email",
                    "profile",
                    "groups",
                    'groups.read',
                    'groups.write',
                    'groups.delete',
                ],
                "token_endpoint_auth_method" => "client_secret_post",
                "frontchannel_logout_uri" => route('auth.frontchannel_logout'),
            ]
        ]);

        $app->fresh();
        $client_id = $app->data['client_id'];
        $client_secret = $app->data['client_secret'];

        shell_exec("sed -i \"s/.*OIDC_MAIN_CLIENT_ID=.*/OIDC_MAIN_CLIENT_ID=$client_id/\" .env");
        shell_exec("sed -i \"s/.*OIDC_MAIN_SECRET=.*/OIDC_MAIN_SECRET=$client_secret/\" .env");

        $app = App::firstOrCreate([
            "id" => 2,
        ], [
            "id" => 2,
            'user_id' => User::first()->id,
            'data' => [
                "client_name" => "Eurofurence IAM Admin",
                "redirect_uris" => [
                    route('filament.auth.callback'),
                ],
                "scope" => [
                    "openid",
                    "offline_access",
                    "email",
                    "profile",
                    "groups",
                    'groups.read',
                    'groups.write',
                    'groups.delete',
                ],
                "token_endpoint_auth_method" => "client_secret_post",
                "frontchannel_logout_uri" => route('filament.auth.frontchannel-logout'),
            ]
        ]);

        $app->fresh();
        $client_id = $app->data['client_id'];
        $client_secret = $app->data['client_secret'];

        shell_exec("sed -i \"s/.*OIDC_ADMIN_CLIENT_ID=.*/OIDC_ADMIN_CLIENT_ID=$client_id/\" .env");
        shell_exec("sed -i \"s/.*OIDC_ADMIN_SECRET=.*/OIDC_ADMIN_SECRET=$client_secret/\" .env");
    }
}
