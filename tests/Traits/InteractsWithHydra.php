<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Http;

trait InteractsWithHydra
{
    protected ?string $hydraTestClientId = null;

    protected function createHydraClient(array $overrides = []): array
    {
        $adminUrl = rtrim(config('services.hydra.admin'), '/') . '/admin/clients';

        $response = Http::post($adminUrl, array_merge([
            'client_name' => 'smoke-test-' . uniqid(),
            'redirect_uris' => ['http://localhost:9999/callback'],
            'grant_types' => ['authorization_code'],
            'response_types' => ['code'],
            'scope' => 'openid email profile',
            'token_endpoint_auth_method' => 'client_secret_post',
        ], $overrides));

        $client = $response->json();
        $this->hydraTestClientId = $client['client_id'];

        return $client;
    }

    protected function getLoginChallenge(?string $clientId = null): string
    {
        $clientId = $clientId ?? $this->hydraTestClientId;
        $publicUrl = rtrim(config('services.hydra.public') ?? str_replace(':4445', ':4444', config('services.hydra.admin')), '/');

        $authUrl = $publicUrl . '/oauth2/auth?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => 'http://localhost:9999/callback',
            'scope' => 'openid email profile',
            'state' => 'teststate-' . uniqid(),
        ]);

        $response = Http::withOptions(['allow_redirects' => false])->get($authUrl);
        $location = $response->header('Location');

        parse_str(parse_url($location, PHP_URL_QUERY), $params);

        if (! isset($params['login_challenge'])) {
            $this->fail('Could not obtain login_challenge from Hydra. Location: ' . $location);
        }

        return $params['login_challenge'];
    }

    protected function deleteHydraClient(?string $clientId = null): void
    {
        $clientId = $clientId ?? $this->hydraTestClientId;

        if ($clientId) {
            $adminUrl = rtrim(config('services.hydra.admin'), '/') . '/admin/clients/' . $clientId;
            Http::delete($adminUrl);
            $this->hydraTestClientId = null;
        }
    }
}
