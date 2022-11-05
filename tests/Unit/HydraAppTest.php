<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HydraAppTest extends TestCase
{
    public function test_create_hydra_app()
    {

        $app = new \App\Services\Hydra\Models\App("http://localhost:4445/");
        $app = $app->create([
            "client_name" => "Test",
            "token_endpoint_auth_method" => "client_secret_post"
        ]);

        //$app->delete();
        $this->assertTrue(true);
    }
}
