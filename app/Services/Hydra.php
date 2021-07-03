<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Ory\Hydra\Client\Api\AdminApi;
use Ory\Hydra\Client\Configuration;
use Ory\Hydra\Client\Model\AcceptConsentRequest;
use Ory\Hydra\Client\Model\AcceptLoginRequest;

class Hydra
{
    private AdminApi $api;

    public function __construct($host = 'hydra:4445')
    {
        $configuration = (new Configuration())->setHost('hydra:4445');
        $this->api = new AdminApi(null, $configuration);
    }

    public function getLoginRequest(string $loginChallenge)
    {
        try {
            $data = $this->api->getLoginRequest($loginChallenge);
            return json_decode($data);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }

            return $e;
        }
    }

    public function acceptLoginRequest(string $userId, string $loginChallenge)
    {
        try {
            $data = $this->api->acceptLoginRequest(
                    $loginChallenge,
                    new AcceptLoginRequest(
                        [
                            'subject' => $userId,
                        ]
                    )
                );
            return json_decode($data);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }
            Log::error($e->getMessage());
            throw $e;
        }
    }

    public function acceptConsentRequest(string $consentChallenge, User $user)
    {
        try {
            $data = $this->api->acceptConsentRequest($consentChallenge, new AcceptConsentRequest([
                'grantScope' => ['openid', 'offline_access'], // array
                'remember' => true, // boolean
                'rememberFor' => 600, // integer
                'session' => [
                    'id_token' => [
                        "global" => [
                            "name" => $user->name,
                            "email" => $user->email,
                            "roles" => $user->roles->pluck('name')
                        ]
                    ]
                ]
            ]));
            return json_decode($data);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }
            throw $e;
        }
    }

    public function getConsentRequest(string $consentRequest)
    {
        try {
            $data = $this->api->getConsentRequest($consentRequest);
            return json_decode($data);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }

            return $e;
        }
    }
}
