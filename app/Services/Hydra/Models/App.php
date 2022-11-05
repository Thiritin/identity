<?php

namespace App\Services\Hydra\Models;

class App extends \App\Services\Hydra\Admin
{
    public string $client_name = "";
    public string $client_id;
    public string|null $client_secret = null;
    public array $allowed_cors_origins = [];
    public array $audience = [];
    public bool $backchannel_logout_session_required = false;
    public string $backchannel_logout_uri = "";
    public string $client_uri = "";
    public array $contacts = [];
    public bool $frontchannel_logout_session_required = false;
    public string $frontchannel_logout_uri = "";
    public array $grant_types = ["authorization_code", "refresh_token"];
    public string $jwks_uri = "";
    public string $logo_uri = "";
    public array $metadata = [];
    public string $owner = "";
    public string $policy_uri = "";
    public array $post_logout_redirect_uris = [];
    public array $redirect_uris = [];
    public string $request_object_signing_alg = "";
    public array $request_uris = [];
    public array $response_types = [];
    public array $scopes = ['openid'];
    public string $sector_identifier_uri = "";
    public string $subject_type = "public";
    public string $token_endpoint_auth_method = "none";
    public string $token_endpoint_auth_signing_alg = "";
    public string $tos_uri = "";
    public string $userinfo_signed_response_alg = "";

    public static function find(string $id)
    {
        return (new App())->load($id);
    }

    private function load(string $id): App
    {
        $data = $this->getRequest("/clients/" . $id);
        $this->fill($data);
        return $this;
    }

    public function create(array $attributes = [])
    {
        $this->fill($attributes);
        $data = $this->postRequest("/clients", $this->payload());
        dd($data);
        $this->fill($data);
        return $this;
    }

    public function delete(): void
    {
        $this->deleteRequest("/clients", [
            "client_id" => $this->client_id
        ]);
    }

    public function update(array $attributes = []): array
    {
        $this->fill($attributes);
        return $this->putRequest("/clients/" . $this->client_id, $this->payload());
    }

    private function payload(): array
    {
        return [
            "client_name" => $this->client_name,
            "client_secret" => $this->client_secret,
            "allowed_cors_origins" => $this->allowed_cors_origins,
            "audience" => $this->audience,
            "backchannel_logout_session_required" => $this->backchannel_logout_session_required,
            "backchannel_logout_uri" => $this->backchannel_logout_uri,
            "client_uri" => $this->client_uri,
            "contacts" => $this->contacts,
            "frontchannel_logout_session_required" => $this->frontchannel_logout_session_required,
            "frontchannel_logout_uri" => $this->frontchannel_logout_uri,
            "grant_types" => $this->grant_types,
            "jwks_uri" => $this->jwks_uri,
            "logo_uri" => $this->logo_uri,
            "metadata" => json_encode($this->metadata, JSON_THROW_ON_ERROR),
            "owner" => $this->owner,
            "policy_uri" => $this->policy_uri,
            "post_logout_redirect_uris" => $this->post_logout_redirect_uris,
            "redirect_uris" => $this->redirect_uris,
            "request_object_signing_alg" => $this->request_object_signing_alg,
            "request_uris" => $this->request_uris,
            "response_types" => $this->response_types,
            "scopes" => implode(" ", $this->scopes),
            "sector_identifier_uri" => $this->sector_identifier_uri,
            "subject_type" => $this->subject_type,
            "token_endpoint_auth_method" => $this->token_endpoint_auth_method,
            "token_endpoint_auth_signing_alg" => $this->token_endpoint_auth_signing_alg,
            "tos_uri" => $this->tos_uri,
            "userinfo_signed_response_alg" => $this->userinfo_signed_response_alg,
        ];
    }


    public function fill(array $attributes): void
    {
        collect($attributes)->each(function ($value, $key) {
            if ($value !== null) {
                if ($key === "metadata") {
                    $this->$key = json_decode($key) ?? [];
                } else {
                    $this->$key = $value;
                }
            }
        });
    }
}
