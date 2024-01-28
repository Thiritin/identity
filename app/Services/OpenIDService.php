<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;

class OpenIDService
{

    public function setupOIDC(Request $request, $systemName): GenericProvider
    {
        $config = Cache::remember('identity_config', now()->addDay(), function () {
            return Http::get(config('services.apps.portal.openid_configuration'))->throw()->json();
        });

        $clientId = config('services.apps')[$systemName]['client_id'];
        $clientSecret = config('services.apps')[$systemName]['client_secret'];
        $clientCallback = route('login.apps.callback', ['app' => $systemName]);

        return new GenericProvider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $clientCallback,
            'urlAuthorize' => $config['authorization_endpoint'],
            'urlAccessToken' => $config['token_endpoint'],
            'urlResourceOwnerDetails' => $config['userinfo_endpoint'],
            'accessTokenMethod' => AbstractProvider::METHOD_POST,
            'scopeSeparator' => ' ',
            'scopes' => explode(" ", config('services.apps')[$systemName]['scopes'])
        ]);
    }
}
