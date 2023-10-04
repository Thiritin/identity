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
                "client_secret" => "optimus",
                "client_name" => "Eurofurence IAM",
                "redirect_uris" => [
                    route('auth.oidc.callback'),
                ],
                "scope" => ["openid", "offline_access", "email", "profile", "groups"],
                "token_endpoint_auth_method" => "client_secret_post",
                "frontchannel_logout_uri" => route('auth.frontchannel_logout'),
            ]
        ]);

        $this->command->info($app->data['client_name']." :".$app->data['client_id']);

        $app = App::firstOrCreate([
            "id" => 2,
        ], [
            "id" => 2,
            'user_id' => User::first()->id,
            'data' => [
                "client_secret" => "optimus",
                "client_name" => "Eurofurence IAM Admin",
                "redirect_uris" => [
                    route('filament.auth.callback'),
                ],
                "scope" => ["openid", "offline_access", "email", "profile", "groups"],
                "token_endpoint_auth_method" => "client_secret_post",
                "frontchannel_logout_uri" => route('filament.auth.frontchannel-logout'),
            ]
        ]);

        $this->command->info($app->data['client_name']." :".$app->data['client_id']);
    }
}
