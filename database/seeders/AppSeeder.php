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
        App::firstOrCreate([
            "id" => 1,
        ], [
            'user_id' => User::first()->id,
            'data' => [
                "client_secret" => "optimus",
                "client_name" => "Eurofurence IAM",
                "redirect_uris" => [
                    route('auth.oidc.callback')
                ],
                "token_endpoint_auth_method" => "client_secret_basic",
                "frontchannel_logout_uri" => route('auth.frontchannel_logout')
            ]
        ]);

        App::firstOrCreate([
            "id" => 2,
        ], [
            'user_id' => User::first()->id,
            'data' => [
                "client_secret" => "optimus",
                "client_name" => "Eurofurence IAM Admin",
                "redirect_uris" => [
                    route('filament.auth.callback')
                ],
                "token_endpoint_auth_method" => "client_secret_basic",
                "frontchannel_logout_uri" => route('filament.auth.frontchannel-logout')
            ]
        ]);
    }
}
