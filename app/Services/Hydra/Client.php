<?php

namespace App\Services\Hydra;

use App\Events\AppLoginEvent;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Client
{
    private $http;

    public function getLoginRequest(string $loginChallenge)
    {
        try {
            return Http::hydraAdmin()->get('/admin/oauth2/auth/requests/login', [
                'challenge' => $loginChallenge,
            ])->json();
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }

            return $e;
        }
    }

    public function getScopes()
    {
        return Http::hydraPublic()->get("/.well-known/openid-configuration")->json('scopes_supported');
    }

    /**
     * Accept OIDC Login Request
     *
     * @param string $login_challenge
     * @return Response
     * @throws JsonException
     */
    public function acceptLogin(string $subject, string $login_challenge, int $remember_seconds = 0, array|null $loginRequest = null): string
    {
        $hydra = new Client();
        // If $loginRequest is not supplied, refetch.
        if ($loginRequest === null) {
            $loginRequest = $hydra->getLoginRequest($login_challenge);
        }

        // redirect_to key is added when login request expired.
        if (isset($loginRequest['redirect_to'])) {
            return $loginRequest['redirect_to'];
        }

        // Accept Login
        $hydraResponse = $hydra->acceptLoginRequest($subject, $login_challenge, $remember_seconds);

        // Send App Login Event
        event(new AppLoginEvent($loginRequest['client']['client_id'], $subject));

        // Throw error on missing redirect
        if (!isset($hydraResponse["redirect_to"])) {
            throw ValidationException::withMessages(['general' => $hydraResponse['error_description'] ?? "Unknown error"]);
        }

        // Return redirect url given in response
        return $hydraResponse['redirect_to'];
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

    public function acceptConsentRequest(array $consentChallenge, User $user)
    {
        try {
            $requestData = [
                'grant_scope' => $consentChallenge['requested_scope'],
                'grant_access_token_audience' => $consentChallenge['requested_access_token_audience'],
                'handled_at' => now(),
            ];

            if (in_array('email', $consentChallenge['requested_scope'])) {
                $requestData['session']['id_token']['email'] = $user->email;
                $requestData['session']['id_token']['email_verified'] = !is_null($user->email_verified_at);
            }
            if (in_array('profile', $consentChallenge['requested_scope'])) {
                $requestData['session']['id_token']['name'] = $user->name;
                $requestData['session']['id_token']['avatar'] = $user->profile_photo_path;
            }
            if (in_array('groups', $consentChallenge['requested_scope'])) {
                $requestData['session']['id_token']['groups'] = $user->groups->pluck('hashid');
            }

            return Http::hydraAdmin()->put('/admin/oauth2/auth/requests/consent/accept?challenge=' . $consentChallenge['challenge'], $requestData)->json();
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }
            throw $e;
        }
    }

    public function rejectConsentRequest(array $consentChallenge, string $errorCode, string $errorDescription, string $errorHint, string $errorDebug, int $statusCode)
    {
        try {
            $requestData = [
                "error" => $errorCode,
                "error_description" => $errorDescription,
                "error_hint" => $errorHint,
                "error_debug" => $errorDebug,
                "status_code" => $statusCode,
            ];
            return Http::hydraAdmin()->put('/admin/oauth2/auth/requests/consent/reject?challenge=' . $consentChallenge['challenge'], $requestData)->json();
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
                'challenge' => $consentChallenge,
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
                'challenge' => $loginChallenge,
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
            return Http::hydraAdmin()->put(
                '/admin/oauth2/auth/requests/logout/accept?challenge=' . $logoutChallenge
            )->json();
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
                    "subject" => $subject,
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

    public function getToken(string $token, array $scopes = [])
    {
        try {
            return Http::hydraAdmin()->asForm()->post('/admin/oauth2/introspect', [
                'token' => $token,
                'scopes' => implode(' ', $scopes),
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
                'scopes' => implode(' ', $scopes),
            ])->json();
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                throw new ModelNotFoundException('The requested Resource does not exist.');
            }

            return $e;
        }
    }
}
