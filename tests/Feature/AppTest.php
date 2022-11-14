<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AppTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_creation()
    {
        Artisan::call('db:seed');
        $app1 = App::firstOrCreate([
            "id" => 1
        ], [
            'user_id' => User::first()->id,
            'data' => [
                "client_name" => "Eurofurence IAM",
                "redirect_uris" => [
                    "http://identity.eurofurence.localhost/auth/callback"
                ],
                "token_endpoint_auth_method" => "post",
                "frontchannel_logout_callback" => "http://identity.eurofurence.localhost/auth/frontchannel-logout"
            ]
        ]);
    }
}
