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
        $app1 = App::firstOrCreate([
            "id" => 1,
        ], [
            'user_id' => User::first()->id,
            'data' => [
                "client_secret" => "optimus",
                "client_name" => "Eurofurence IAM",
                "redirect_uris" => [
                    "http://identity.eurofurence.localhost/auth/callback"
                ],
                "token_endpoint_auth_method" => "client_secret_basic",
                "frontchannel_logout_callback" => "http://identity.eurofurence.localhost/auth/frontchannel-logout"
            ]
        ]);
    }
}
