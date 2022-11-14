<?php

namespace Tests\Unit;

use App\Services\Hydra\Models\App;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HydraAppTest extends TestCase
{
    private App $hydraApp;
    public function test_create_hydra_app()
    {
        $app = new \App\Services\Hydra\Models\App();
        $app = $app->create([
            "client_name" => "Test",
            "token_endpoint_auth_method" => "client_secret_post"
        ]);
        $app->delete();

        $this->assertNotNull($app->client_secret);
    }

    public function test_update_hydra_app()
    {
        $initialApp = new \App\Services\Hydra\Models\App();
        $initialApp->create([
            "client_name" => "Test",
            "token_endpoint_auth_method" => "client_secret_post",
            "client_secret" => "test123"
        ]);
        $app = App::find($initialApp->client_id);

        $app->update([
            "client_name" => "New"
        ]);
        $this->assertEquals($app->client_name,"New");
        $this->assertEquals($app->token_endpoint_auth_method,"client_secret_post");
        $this->assertNull($app->client_secret);



    }

    public function test_delete_hydra_app()
    {
        $app = new \App\Services\Hydra\Models\App();
        $app = $app->create([
            "client_name" => "Test",
            "token_endpoint_auth_method" => "client_secret_post"
        ]);
        $this->assertTrue($app->delete());
    }
}
