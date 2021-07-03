<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminAuth implements Guard
{
    protected UserProvider $provider;

    /**
     * The currently authenticated user.
     *
     * @var Authenticatable
     */
    protected $user;

    public function __construct(UserProvider $provider)
    {
        $this->provider = $provider;
    }

    public function guest()
    {
        return !$this->check();
    }

    public function check()
    {
        return $this->user !== null;
    }

    public function id()
    {
        if ($user = $this->user()) {
            return $this->user()->getAuthIdentifier();
        }
    }

    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }
    }

    public function validate(array $credentials = []): bool
    {
        if (!$cookie = Cookie::get('jwt')) {
            return false;
        }
        $signingKey = $this->getSigningKey();
    }

    protected function getSigningKey()
    {
        if ($signingkey = Cache::get('jwt_publickey')) {
            return $signingkey;
        }

        $response = Http::get(config('services.hydra.public.url'));
        if($response->failed()) {
            Log::error('Hydra\'s JWK Endpoint is not reachable Response Code: '.$response->status());
            abort('500', 'We\'re unable to reach the Authentication Server. Please try again later.');
        }
        if (!$body = json_decode($response->body())) {
            Log::error('Unable to decode body of jwt update response'.$response->body());
            abort('500', 'We\'re unable to reach the Authentication Server. Please try again later.');
        }
        if (!$key = $body->keys[0]) {
            abort('500', 'No keys available to verify your session.');
        }
        if (Cache::set('jwt_publickey', $key, '1d')) {
            Log::info('Updated id_token jwt public key successfully.');
        }
        return $key;
    }

    public function setUser(Authenticatable $user)
    {
        $this->user = $user;

        return $this;
    }
}
