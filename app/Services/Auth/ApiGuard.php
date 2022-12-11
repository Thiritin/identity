<?php

namespace App\Services\Auth;

use App\Services\Hydra\Client;
use Hashids;
use Illuminate\Auth\TokenGuard;

class ApiGuard extends TokenGuard
{
    private array $scopes = [];

    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (!is_null($this->user)) {
            return $this->user;
        }

        $user = null;
        $token = $this->getTokenForRequest();

        if (!empty($token)) {
            $hydra = new Client();
            $response = $hydra->getToken($token);

            if ($response['active'] === true) {
                $this->scopes = explode(" ", $response['scope']);
                $user = $this->provider->retrieveByCredentials([
                    'id' => Hashids::decode($response['sub'])
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
        return $response["active"] === true;
    }

    public function getScopes()
    {
        return $this->scopes;
    }
}
