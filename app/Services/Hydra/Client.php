<?php

namespace App\Services\Hydra;

use App\Models\User;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class Client
{
    private GuzzleClient $http;

    public function __construct($host = 'hydra:4445')
    {
        $this->http = new GuzzleClient([
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

    public function acceptLoginRequest(string $userId, string $loginChallenge, int $remember = 0)
    {
        try {
            $response = $this->http->put('/oauth2/auth/requests/login/accept?challenge='.$loginChallenge, [
                'query' => [
                    'challenge' => $loginChallenge,
                ],
                'body' => json_encode([
                    'subject' => $userId,
                    'remember' => ($remember === 0) ? false : true,
                    'remember_for' => $remember,
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

    public function getLogoutRequest(string $loginChallenge)
    {
        try {
            $response = $this->http->get('/oauth2/auth/requests/logout', [
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

    public function acceptLogoutRequest(string $logoutChallenge)
    {
        try {
            $response = $this->http->put('/oauth2/auth/requests/logout/accept?challenge=' . $logoutChallenge, [
                'query' => [
                    'challenge' => $logoutChallenge,
                ]
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

    public function invalidateAllSessions(string $subject)
    {
        try {
            $response = $this->http->delete('/oauth2/auth/sessions/login', [
                'query' => [
                    "subject" => $subject
                ],
            ]);
            return $response->getStatusCode() === 204;
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }
            Log::error($e->getMessage());
            throw $e;
        }
    }

    public function getToken(string $token, array $scopes)
    {
        try {
            $response = $this->http->post('/oauth2/introspect', [
                'form_params' => [
                    'token' => $token,
                    'scopes' => implode(' ', $scopes)
                ],
            ]);
            return json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }

            return $e;
        }
    }

    public function logout(string $token, array $scopes)
    {
        try {
            $response = $this->http->post('/oauth2/introspect', [
                'form_params' => [
                    'token' => $token,
                    'scopes' => implode(' ', $scopes)
                ],
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
