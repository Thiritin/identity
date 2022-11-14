<?php

namespace App\Services\Hydra;

use App\Models\User;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client
{
    private $http;

    public function getLoginRequest(string $loginChallenge)
    {
        try {
            return Http::hydraAdmin()->get('/admin/oauth2/auth/requests/login', [
                'challenge' => $loginChallenge
            ])->json();
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
            return Http::hydraAdmin()->put('/admin/oauth2/auth/requests/login/accept?challenge=' . $loginChallenge, [
                'subject' => $userId,
                'remember' => ($remember === 0) ? false : true,
                'remember_for' => $remember,
            ])->json();

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
            $response = Http::hydraAdmin()->put('/admin/oauth2/auth/requests/consent/accept?challenge=' . $consentChallenge, [
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
            return Http::hydraAdmin()->get('/admin/oauth2/auth/requests/consent', [
                'challenge' => $consentChallenge
            ])->json();
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
            return Http::hydraAdmin()->get('/admin/oauth2/auth/requests/logout', [
                'challenge' => $loginChallenge
            ])->json();
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
            return Http::hydraAdmin()->put('/admin/oauth2/auth/requests/logout/accept?challenge=' . $logoutChallenge)->jsom();
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
            return Http::hydraAdmin()->delete('/admin/oauth2/auth/sessions/login', [
                'query' => [
                    "subject" => $subject
                ],
            ])->successful();
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
            return Http::hydraAdmin()->post('/admin/oauth2/introspect', [
                'token' => $token,
                'scopes' => implode(' ', $scopes)
            ])->json();
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
            return Http::hydraAdmin()->post('/admin/oauth2/introspect', [
                'token' => $token,
                'scopes' => implode(' ', $scopes)
            ])->json();
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }

            return $e;
        }
    }
}
