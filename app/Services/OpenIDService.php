<?php

namespace App\Services;

use Cache;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;

class OpenIDService
{

    public function setupOIDC(Request $request, bool $clientIsAdmin): GenericProvider
    {
        $config = Cache::remember('openid-configuration', now()->addHour(), function () {
            return Http::get(config('services.hydra.public') . "/.well-known/openid-configuration")->json();
        });

        if ($clientIsAdmin) {
            $clientId = config('services.oidc.admin.client_id');
            $clientSecret = config('services.oidc.admin.secret');
            $clientCallback = route('filament.auth.callback');
        } else {
            $clientId = config('services.oidc.main.client_id');
            $clientSecret = config('services.oidc.main.secret');
            $clientCallback = route('auth.oidc.callback');
        }

        if (App::isLocal()) {
            return new GenericProvider([
                'clientId'                => $clientId,
                'clientSecret'            => $clientSecret,
                'redirectUri'             => $clientCallback,
                'urlAuthorize'            => config('services.hydra.local_public') . "/oauth2/auth",
                'urlAccessToken'          => config('services.hydra.public') . "/oauth2/token",
                'urlResourceOwnerDetails' => $config['userinfo_endpoint'],
                'accessTokenMethod'       => AbstractProvider::METHOD_POST,
                'scopeSeparator'          => ' ',
                'scopes'                  => ['openid', 'offline_access', 'email', 'profile', 'groups'],
            ]);
        }

        return new GenericProvider([
            'clientId'                => $clientId,
            'clientSecret'            => $clientSecret,
            'redirectUri'             => $clientCallback,
            'urlAuthorize'            => $config['authorization_endpoint'],
            'urlAccessToken'          => $config['token_endpoint'],
            'urlResourceOwnerDetails' => $config['userinfo_endpoint'],
            'accessTokenMethod'       => AbstractProvider::METHOD_POST,
            'scopeSeparator'          => ' ',
            'scopes'                  => ['openid', 'offline_access', 'email', 'profile', 'groups'],
        ]);
    }
}
