<?php

namespace App\Services;

use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Ory\Hydra\Client\Api\AdminApi;

class Hydra
{
    private AdminApi $api;
    private Client $http;

    public function __construct($host = 'hydra:4445')
    {
        $this->http = new Client([
            'base_uri' => config('services.hydra.admin'),
            'verify' => false,
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function getLoginRequest(string $loginChallenge)
    {
        try {
            $response = $this->http->get('/oauth2/auth/requests/login', [
                'query' => [
                    'challenge' => $loginChallenge
                ]
            ]);
            return json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
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
            $response = $this->http->put('/oauth2/auth/requests/login/accept?challenge='.$loginChallenge, [
                'query' => [
                    'challenge' => $loginChallenge,
                ],
                'body' => json_encode([
                    'acr' => 'default',
                    'subject' => $userId,
                    'remember' => false, // Add option for remember submission onto login
                    'remember_for' => 3600,
                ], JSON_THROW_ON_ERROR)
            ]);

            return json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
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
            $response = $this->http->put('/oauth2/auth/requests/consent/accept', [
                'query' => [
                    'challenge' => $consentChallenge
                ],
                'body' => json_encode([
                    'grant_scope' => ['openid', 'offline_access'], // array
                    //'remember' => true, // boolean
                    //'rememberFor' => 600, // integer
                    'handled_at' => now(),
                    'session' => [
                        'id_token' => [
                            "global" => [
                                "name" => $user->name,
                                "email" => $user->email,
                                "email_verified" => $user->hasVerifiedEmail(),
                                "roles" => $user->roles->pluck('name'),
                            ]
                        ]
                    ]
                ], JSON_THROW_ON_ERROR)
            ]);
            return json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }
            throw $e;
        }
    }

    public function getConsentRequest(string $consentChallenge)
    {
        try {
            $response = $this->http->get('/oauth2/auth/requests/consent', [
                'query' => [
                    'challenge' => $consentChallenge
                ]
            ]);
            return json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }

            return $e;
        }
    }

    public function getToken(string $token, array $scopes)
    {
        try {
            $response = $this->http->post('/oauth2/introspect', [
                'body' => json_encode([
                    'token' => $token,
                    'scopes' => implode(' ', $scopes)
                ], JSON_THROW_ON_ERROR),
            ]);
            return json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }

            return $e;
        }
    }
}
