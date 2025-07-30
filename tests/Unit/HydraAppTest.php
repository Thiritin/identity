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
        Http::fake([
            '*' => Http::response([
                'client_id' => '2b83b3ea-7021-455f-b07d-9c00c74436f2',
                'client_name' => 'Test',
                'client_secret' => 'mM~CL93svezjf-5Yr3xG51u6jm',
                'redirect_uris' => null,
                'grant_types' => [
                    0 => 'authorization_code',
                    1 => 'refresh_token',
                ],
                'response_types' => [
                    0 => 'code',
                ],
                'scope' => 'openid',
                'audience' => [],
                'owner' => '',
                'policy_uri' => '',
                'allowed_cors_origins' => [],
                'tos_uri' => '',
                'client_uri' => '',
                'logo_uri' => '',
                'contacts' => null,
                'client_secret_expires_at' => 0,
                'subject_type' => 'public',
                'jwks' => [],
                'token_endpoint_auth_method' => 'client_secret_post',
                'request_object_signing_alg' => 'RS256',
                'userinfo_signed_response_alg' => 'none',
                'created_at' => '2023-02-05T20:17:23Z',
                'updated_at' => '2023-02-05T20:17:23.301609Z',
                'metadata' => [],
                'registration_access_token' => 'ory_at_udqWbEdg3tE_dcwJT2-8SjM107L5trNo6aGpz0kCJ3w.yEiDwxN-lEDus4PNWDA_WhYOO-ZYJofvRc-EK_5d-00',
                'registration_client_uri' => 'http://identity.eurofurence.lan/oauth2/register/2b83b3ea-7021-455f-b07d-9c00c74436f2',
                'authorization_code_grant_access_token_lifespan' => null,
                'authorization_code_grant_id_token_lifespan' => null,
                'authorization_code_grant_refresh_token_lifespan' => null,
                'client_credentials_grant_access_token_lifespan' => null,
                'implicit_grant_access_token_lifespan' => null,
                'implicit_grant_id_token_lifespan' => null,
                'jwt_bearer_grant_access_token_lifespan' => null,
                'refresh_token_grant_id_token_lifespan' => null,
                'refresh_token_grant_access_token_lifespan' => null,
                'refresh_token_grant_refresh_token_lifespan' => null,
            ]),
        ]);
        $app = new App();
        $app = $app->create([
            'client_name' => 'Test',
            'token_endpoint_auth_method' => 'client_secret_post',
        ]);
        $app->delete();

        $this->assertNotNull($app->client_secret);
    }

    public function test_update_hydra_app()
    {
        Http::fake([
            'http://localhost:4445/admin/clients' => [
                'client_id' => '2b83b3ea-7021-455f-b07d-9c00c74436f2',
                'client_name' => 'Test',
                'client_secret' => 'mM~CL93svezjf-5Yr3xG51u6jm',
                'redirect_uris' => null,
                'grant_types' => [
                    0 => 'authorization_code',
                    1 => 'refresh_token',
                ],
                'response_types' => [
                    0 => 'code',
                ],
                'scope' => 'openid',
                'audience' => [],
                'owner' => '',
                'policy_uri' => '',
                'allowed_cors_origins' => [],
                'tos_uri' => '',
                'client_uri' => '',
                'logo_uri' => '',
                'contacts' => null,
                'client_secret_expires_at' => 0,
                'subject_type' => 'public',
                'jwks' => [],
                'token_endpoint_auth_method' => 'client_secret_post',
                'request_object_signing_alg' => 'RS256',
                'userinfo_signed_response_alg' => 'none',
                'created_at' => '2023-02-05T20:17:23Z',
                'updated_at' => '2023-02-05T20:17:23.301609Z',
                'metadata' => [],
                'registration_access_token' => 'ory_at_udqWbEdg3tE_dcwJT2-8SjM107L5trNo6aGpz0kCJ3w.yEiDwxN-lEDus4PNWDA_WhYOO-ZYJofvRc-EK_5d-00',
                'registration_client_uri' => 'http://identity.eurofurence.lan/oauth2/register/2b83b3ea-7021-455f-b07d-9c00c74436f2',
                'authorization_code_grant_access_token_lifespan' => null,
                'authorization_code_grant_id_token_lifespan' => null,
                'authorization_code_grant_refresh_token_lifespan' => null,
                'client_credentials_grant_access_token_lifespan' => null,
                'implicit_grant_access_token_lifespan' => null,
                'implicit_grant_id_token_lifespan' => null,
                'jwt_bearer_grant_access_token_lifespan' => null,
                'refresh_token_grant_id_token_lifespan' => null,
                'refresh_token_grant_access_token_lifespan' => null,
                'refresh_token_grant_refresh_token_lifespan' => null,
            ],
            'http://localhost:4445/admin/clients/2b83b3ea-7021-455f-b07d-9c00c74436f2' => [
                'client_id' => '2b83b3ea-7021-455f-b07d-9c00c74436f2',
                'client_name' => 'New',
                'client_secret' => null,
                'redirect_uris' => null,
                'grant_types' => [
                    0 => 'authorization_code',
                    1 => 'refresh_token',
                ],
                'response_types' => [
                    0 => 'code',
                ],
                'scope' => 'openid',
                'audience' => [],
                'owner' => '',
                'policy_uri' => '',
                'allowed_cors_origins' => [],
                'tos_uri' => '',
                'client_uri' => '',
                'logo_uri' => '',
                'contacts' => null,
                'client_secret_expires_at' => 0,
                'subject_type' => 'public',
                'jwks' => [],
                'token_endpoint_auth_method' => 'client_secret_post',
                'request_object_signing_alg' => 'RS256',
                'userinfo_signed_response_alg' => 'none',
                'created_at' => '2023-02-05T20:17:23Z',
                'updated_at' => '2023-02-05T20:17:23.301609Z',
                'metadata' => [],
                'registration_access_token' => 'ory_at_udqWbEdg3tE_dcwJT2-8SjM107L5trNo6aGpz0kCJ3w.yEiDwxN-lEDus4PNWDA_WhYOO-ZYJofvRc-EK_5d-00',
                'registration_client_uri' => 'http://identity.eurofurence.lan/oauth2/register/2b83b3ea-7021-455f-b07d-9c00c74436f2',
                'authorization_code_grant_access_token_lifespan' => null,
                'authorization_code_grant_id_token_lifespan' => null,
                'authorization_code_grant_refresh_token_lifespan' => null,
                'client_credentials_grant_access_token_lifespan' => null,
                'implicit_grant_access_token_lifespan' => null,
                'implicit_grant_id_token_lifespan' => null,
                'jwt_bearer_grant_access_token_lifespan' => null,
                'refresh_token_grant_id_token_lifespan' => null,
                'refresh_token_grant_access_token_lifespan' => null,
                'refresh_token_grant_refresh_token_lifespan' => null,
            ],
        ]);

        $initialApp = new App();
        $initialApp->create([
            'client_name' => 'Test',
            'token_endpoint_auth_method' => 'client_secret_post',
            'client_secret' => 'test123',
        ]);
        $app = App::find($initialApp->client_id);

        $app->update([
            'client_name' => 'New',
        ]);
        $this->assertEquals('New', $app->client_name);
        $this->assertEquals($app->token_endpoint_auth_method, 'client_secret_post');
        $this->assertNull($app->client_secret);
    }

    public function test_delete_hydra_app()
    {
        Http::fake([
            '*' => Http::response([
                'client_id' => '2b83b3ea-7021-455f-b07d-9c00c74436f2',
                'client_name' => 'New',
                'client_secret' => null,
                'redirect_uris' => null,
                'grant_types' => [
                    0 => 'authorization_code',
                    1 => 'refresh_token',
                ],
                'response_types' => [
                    0 => 'code',
                ],
                'scope' => 'openid',
                'audience' => [],
                'owner' => '',
                'policy_uri' => '',
                'allowed_cors_origins' => [],
                'tos_uri' => '',
                'client_uri' => '',
                'logo_uri' => '',
                'contacts' => null,
                'client_secret_expires_at' => 0,
                'subject_type' => 'public',
                'jwks' => [],
                'token_endpoint_auth_method' => 'client_secret_post',
                'request_object_signing_alg' => 'RS256',
                'userinfo_signed_response_alg' => 'none',
                'created_at' => '2023-02-05T20:17:23Z',
                'updated_at' => '2023-02-05T20:17:23.301609Z',
                'metadata' => [],
                'registration_access_token' => 'ory_at_udqWbEdg3tE_dcwJT2-8SjM107L5trNo6aGpz0kCJ3w.yEiDwxN-lEDus4PNWDA_WhYOO-ZYJofvRc-EK_5d-00',
                'registration_client_uri' => 'http://identity.eurofurence.lan/oauth2/register/2b83b3ea-7021-455f-b07d-9c00c74436f2',
                'authorization_code_grant_access_token_lifespan' => null,
                'authorization_code_grant_id_token_lifespan' => null,
                'authorization_code_grant_refresh_token_lifespan' => null,
                'client_credentials_grant_access_token_lifespan' => null,
                'implicit_grant_access_token_lifespan' => null,
                'implicit_grant_id_token_lifespan' => null,
                'jwt_bearer_grant_access_token_lifespan' => null,
                'refresh_token_grant_id_token_lifespan' => null,
                'refresh_token_grant_access_token_lifespan' => null,
                'refresh_token_grant_refresh_token_lifespan' => null,
            ]),
        ]);
        $app = new App();
        $app = $app->create([
            'client_name' => 'Test',
            'token_endpoint_auth_method' => 'client_secret_post',
        ]);
        $this->assertTrue($app->delete());
    }
}
