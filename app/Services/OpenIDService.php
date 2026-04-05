<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;

class OpenIDService
{
    public function setupOIDC(Request $request): GenericProvider
    {
        $config = Cache::remember('identity_config', now()->addDay(), function () {
            return Http::get(config('services.apps.identity.openid_configuration'))->throw()->json();
        });

        return new GenericProvider([
            'clientId' => config('services.apps.identity.client_id'),
            'clientSecret' => config('services.apps.identity.client_secret'),
            'redirectUri' => route('login.callback'),
            'urlAuthorize' => $config['authorization_endpoint'],
            'urlAccessToken' => $config['token_endpoint'],
            'urlResourceOwnerDetails' => $config['userinfo_endpoint'],
            'accessTokenMethod' => AbstractProvider::METHOD_POST,
            'scopeSeparator' => ' ',
            'scopes' => explode(' ', config('services.apps.identity.scopes')),
        ]);
    }
}
