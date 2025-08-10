<?php

namespace App\Services\Auth;

use App\Services\Hydra\Client;
use Hashids;
use Illuminate\Auth\TokenGuard;

class ApiGuard extends TokenGuard
{
    private array $scopes = [];

    private array $audiences = [];

    private string $client_id = '';

    private string $exp = '';

    private string $iat = '';

    private string $nbf = '';

    private string $iss = '';

    private string $token_type = '';

    private string $token_use = '';

    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = null;
        $token = $this->getTokenForRequest();

        if (! empty($token)) {
            $hydra = new Client();
            $response = $hydra->getToken($token);

            // Handle exception case (when Hydra is not available or connection fails)
            if ($response instanceof \Exception) {
                return null;
            }

            if (is_array($response) && ($response['active'] ?? false) === true) {
                $this->audiences = $response['aud'];
                $this->client_id = $response['client_id'];
                $this->exp = $response['exp'];
                $this->iat = $response['iat'];
                $this->nbf = $response['nbf'];
                $this->iss = $response['iss'];
                $this->token_type = $response['token_type'];
                $this->token_use = $response['token_use'];
                $this->scopes = explode(' ', $response['scope']);
                $user = $this->provider->retrieveByCredentials([
                    'id' => Hashids::connection('user')->decode($response['sub']),
                ]);
            }
        }

        return $this->user = $user;
    }

    public function validate(array $credentials = [])
    {
        if (empty($credentials[$this->inputKey])) {
            return false;
        }

        $hydra = new Client();
        $response = $hydra->getToken($credentials[$this->inputKey]);

        // Handle exception case (when Hydra is not available or connection fails)
        if ($response instanceof \Exception) {
            return false;
        }

        return is_array($response) && ($response['active'] ?? false) === true;
    }

    public function getScopes()
    {
        return $this->scopes;
    }

    public function getAudiences()
    {
        return $this->audiences;
    }
}
