<?php

namespace App\Providers\Socialite;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class SocialiteIdentityProvider extends AbstractProvider
{
    private mixed $issuer;

    private mixed $userinfoEndpoint;

    private mixed $tokenEndpoint;

    private mixed $authorizationEndpoint;

    private mixed $jwksUri;

    private mixed $endSessionEndpoint;

    private mixed $revocationEndpoint;

    private ?string $idToken = null;

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    public function getIdentityConfig()
    {
        // Get from cache if exists
        if (isset($this->issuer)) {
            return $this;
        }
        // Get from services.identity.openid_configuration url and cache it
        $config = Cache::remember('identity_config', now()->addDay(), function () {
            return Http::get(config('services.apps.identity.openid_configuration'))->throw()->json();
        });
        $this->issuer = $config['issuer'];
        $this->userinfoEndpoint = $config['userinfo_endpoint'];
        $this->authorizationEndpoint = $config['authorization_endpoint'];
        $this->tokenEndpoint = $config['token_endpoint'];
        $this->jwksUri = $config['jwks_uri'];
        $this->endSessionEndpoint = $config['end_session_endpoint'];
        $this->revocationEndpoint = $config['revocation_endpoint'];

        return $this;
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getIdentityConfig()->authorizationEndpoint, $state);
    }

    protected function getTokenUrl()
    {
        return $this->getIdentityConfig()->tokenEndpoint;
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getIdentityConfig()->userinfoEndpoint, [
            'headers' => [
                'cache-control' => 'no-cache',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['sub'],
            'email' => $user['email'],
            'email_verified' => $user['email_verified'],
            'name' => $user['name'],
            'groups' => $user['groups'],
        ]);
    }

    protected function userInstance(array $response, array $user)
    {
        $this->idToken = Arr::get($response, 'id_token');

        return parent::userInstance($response, $user);
    }

    public function logoutAll()
    {
        return Redirect::to($this->getIdentityConfig()->endSessionEndpoint);
    }

    public function putToken(
        ?string $token,
        ?string $refreshToken,
        ?Carbon $expiresIn
    ) {
        if ($token) {
            Session::put($this->clientId . '.token.value', $token);
            Session::put($this->clientId . '.token.expiry', $expiresIn);
            Session::put($this->clientId . '.refreshToken', $refreshToken);
        }
    }

    public function clearToken(): void
    {
        Session::forget($this->clientId . '.token.value');
        Session::forget($this->clientId . '.token.expiry');
        Session::forget($this->clientId . '.refreshToken');
    }

    public function getToken(): ?string
    {
        return Session::get($this->clientId . '.token.value');
    }

    public function getRefreshToken(): ?string
    {
        return Session::get($this->clientId . '.refreshToken');
    }

    public function getExpiresIn(): ?Carbon
    {
        return Session::get($this->clientId . '.token.expiry');
    }

    public function getJwks(): array
    {
        $this->getIdentityConfig();

        return Cache::remember('hydra_jwks', now()->addHour(), function () {
            return Http::get($this->jwksUri)->throw()->json();
        });
    }

    public function getSid(): ?string
    {
        if ($this->idToken === null) {
            return null;
        }

        try {
            $jwks = $this->getJwks();
            $decoded = JWT::decode($this->idToken, JWK::parseKeySet($jwks));

            return $decoded->sid ?? null;
        } catch (\Exception $e) {
            Log::warning('Failed to decode sid from ID token', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
