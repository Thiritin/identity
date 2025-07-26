<?php

namespace App\Services\Hydra\Models;

use App\Domains\Auth\Services\Hydra\Admin;

class App extends Admin
{
    public string $client_name = '';

    public string $client_id = '';

    public ?string $client_secret = null;

    public array $allowed_cors_origins = [];

    public array $audience = [];

    public bool $backchannel_logout_session_required = false;

    public string $backchannel_logout_uri = '';

    public string $client_uri = '';

    public array $contacts = [];

    public bool $frontchannel_logout_session_required = false;

    public string $frontchannel_logout_uri = '';

    public array $grant_types = ['authorization_code', 'refresh_token'];

    public string $jwks_uri = '';

    public string $logo_uri = '';

    public array $metadata = [];

    public string $owner = '';

    public string $policy_uri = '';

    public array $post_logout_redirect_uris = [];

    public array $redirect_uris = [];

    public string $request_object_signing_alg = 'RS256';

    public array $request_uris = [];

    public array $response_types = ['code'];

    public array $scope = ['openid'];

    public string $sector_identifier_uri = '';

    public string $subject_type = 'public';

    public string $token_endpoint_auth_method = 'none';

    public string $token_endpoint_auth_signing_alg = '';

    public string $tos_uri = '';

    public string $userinfo_signed_response_alg = '';

    public string $authorization_code_grant_access_token_lifespan = '';

    public string $authorization_code_grant_id_token_lifespan = '';

    public string $authorization_code_grant_refresh_token_lifespan = '';

    public string $client_credentials_grant_access_token_lifespan = '';

    public string $implicit_grant_access_token_lifespan = '';

    public string $implicit_grant_id_token_lifespan = '';

    public string $jwt_bearer_grant_access_token_lifespan = '';

    public string $refresh_token_grant_access_token_lifespan = '';

    public string $refresh_token_grant_id_token_lifespan = '';

    public string $refresh_token_grant_refresh_token_lifespan = '';

    public static function find(string $id)
    {
        return (new App())->load($id);
    }

    private function load(string $id): App
    {
        $data = $this->getRequest('/clients/' . $id);
        $this->fill($data);

        return $this;
    }

    public function create(array $attributes = [])
    {
        $this->fill($attributes);
        $data = $this->postRequest('/clients', $this->formPayload());
        $this->fill($data);

        return $this;
    }

    public function delete(): bool
    {
        return $this->deleteRequest('/clients/' . $this->client_id);
    }

    public function get(): array
    {
        $data = $this->getRequest('/clients/' . $this->client_id);
        $this->fill($data);

        return $data;
    }

    public function update(array $attributes = []): array
    {
        $this->fill($attributes);
        $data = $this->putRequest('/clients/' . $this->client_id, $this->formPayload());
        $this->fill($data);

        return $data;
    }

    public function payload(): array
    {
        return [
            'client_name' => $this->client_name,
            'client_secret' => $this->client_secret,
            'allowed_cors_origins' => $this->allowed_cors_origins,
            'audience' => $this->audience,
            'backchannel_logout_session_required' => $this->backchannel_logout_session_required,
            'backchannel_logout_uri' => $this->backchannel_logout_uri,
            'client_uri' => $this->client_uri,
            'contacts' => $this->contacts,
            'frontchannel_logout_session_required' => $this->frontchannel_logout_session_required,
            'frontchannel_logout_uri' => $this->frontchannel_logout_uri,
            'grant_types' => $this->grant_types,
            'jwks_uri' => $this->jwks_uri,
            'logo_uri' => $this->logo_uri,
            'owner' => $this->owner,
            'policy_uri' => $this->policy_uri,
            'post_logout_redirect_uris' => $this->post_logout_redirect_uris,
            'redirect_uris' => $this->redirect_uris,
            'request_object_signing_alg' => $this->request_object_signing_alg,
            'request_uris' => $this->request_uris,
            'response_types' => $this->response_types,
            'scope' => implode(' ', $this->scope),
            'sector_identifier_uri' => $this->sector_identifier_uri,
            'subject_type' => $this->subject_type,
            'token_endpoint_auth_method' => $this->token_endpoint_auth_method,
            'token_endpoint_auth_signing_alg' => $this->token_endpoint_auth_signing_alg,
            'tos_uri' => $this->tos_uri,
            'userinfo_signed_response_alg' => $this->userinfo_signed_response_alg,
            // Lifespans
            'authorization_code_grant_access_token_lifespan' => $this->authorization_code_grant_access_token_lifespan,
            'authorization_code_grant_id_token_lifespan' => $this->authorization_code_grant_id_token_lifespan,
            'authorization_code_grant_refresh_token_lifespan' => $this->authorization_code_grant_refresh_token_lifespan,
            'client_credentials_grant_access_token_lifespan' => $this->client_credentials_grant_access_token_lifespan,
            'implicit_grant_access_token_lifespan' => $this->implicit_grant_access_token_lifespan,
            'implicit_grant_id_token_lifespan' => $this->implicit_grant_id_token_lifespan,
            'jwt_bearer_grant_access_token_lifespan' => $this->jwt_bearer_grant_access_token_lifespan,
            'refresh_token_grant_access_token_lifespan' => $this->refresh_token_grant_access_token_lifespan,
            'refresh_token_grant_id_token_lifespan' => $this->refresh_token_grant_id_token_lifespan,
            'refresh_token_grant_refresh_token_lifespan' => $this->refresh_token_grant_refresh_token_lifespan,
        ];
    }

    public function formPayload()
    {
        return collect($this->payload())->reject(fn ($v) => empty($v))->toArray();
    }

    public function fill(array $attributes): void
    {
        collect($attributes)->each(function ($value, $key) {
            if ($value !== null) {
                if ($key === 'scope') {
                    if (! is_array($value)) {
                        $this->$key = explode(' ', $value);
                    } else {
                        $this->$key = $value;
                    }
                } elseif ($key === 'metadata') {
                    if (! is_array($value)) {
                        $this->$key = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    } else {
                        $this->$key = $value;
                    }
                } else {
                    $this->$key = $value;
                }
            }
        });
    }

    public function toArray(): array
    {
        return [
            'client_id' => $this->client_id,
            'client_name' => $this->client_name,
            'client_secret' => $this->client_secret,
            'allowed_cors_origins' => $this->allowed_cors_origins,
            'audience' => $this->audience,
            'backchannel_logout_session_required' => $this->backchannel_logout_session_required,
            'backchannel_logout_uri' => $this->backchannel_logout_uri,
            'client_uri' => $this->client_uri,
            'contacts' => $this->contacts,
            'frontchannel_logout_session_required' => $this->frontchannel_logout_session_required,
            'frontchannel_logout_uri' => $this->frontchannel_logout_uri,
            'grant_types' => $this->grant_types,
            'jwks_uri' => $this->jwks_uri,
            'logo_uri' => $this->logo_uri,
            'metadata' => $this->metadata,
            'owner' => $this->owner,
            'policy_uri' => $this->policy_uri,
            'post_logout_redirect_uris' => $this->post_logout_redirect_uris,
            'redirect_uris' => $this->redirect_uris,
            'request_object_signing_alg' => $this->request_object_signing_alg,
            'request_uris' => $this->request_uris,
            'response_types' => $this->response_types,
            'scope' => $this->scope,
            'sector_identifier_uri' => $this->sector_identifier_uri,
            'subject_type' => $this->subject_type,
            'token_endpoint_auth_method' => $this->token_endpoint_auth_method,
            'token_endpoint_auth_signing_alg' => $this->token_endpoint_auth_signing_alg,
            'tos_uri' => $this->tos_uri,
            'userinfo_signed_response_alg' => $this->userinfo_signed_response_alg,
            // Lifespans
            'authorization_code_grant_access_token_lifespan' => $this->authorization_code_grant_access_token_lifespan,
            'authorization_code_grant_id_token_lifespan' => $this->authorization_code_grant_id_token_lifespan,
            'authorization_code_grant_refresh_token_lifespan' => $this->authorization_code_grant_refresh_token_lifespan,
            'client_credentials_grant_access_token_lifespan' => $this->client_credentials_grant_access_token_lifespan,
            'implicit_grant_access_token_lifespan' => $this->implicit_grant_access_token_lifespan,
            'implicit_grant_id_token_lifespan' => $this->implicit_grant_id_token_lifespan,
            'jwt_bearer_grant_access_token_lifespan' => $this->jwt_bearer_grant_access_token_lifespan,
            'refresh_token_grant_access_token_lifespan' => $this->refresh_token_grant_access_token_lifespan,
            'refresh_token_grant_id_token_lifespan' => $this->refresh_token_grant_id_token_lifespan,
            'refresh_token_grant_refresh_token_lifespan' => $this->refresh_token_grant_refresh_token_lifespan,
        ];
    }
}
