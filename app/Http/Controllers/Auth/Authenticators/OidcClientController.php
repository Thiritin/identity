<?php

namespace App\Http\Controllers\Auth\Authenticators;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;
use Vinkla\Hashids\Facades\Hashids;
use function abort;
use function config;
use function route;

class OidcClientController extends Controller
{
    /**
     * @throws \Jumbojett\OpenIDConnectClientException
     */
    public function callback()
    {
        $oidc = $this->setupOIDC();
        try {
            if ($oidc->authenticate()) {
                if (!$oidc->verifyJWTsignature($oidc->getIdToken())) abort(403, 'ID Token invalid.');
                Auth::guard('web')->loginUsingId(Hashids::decode($oidc->getIdTokenPayload()->sub));
                Session::put('id_token', $oidc->getIdToken());
                return Redirect::route('dashboard');
            }
        } catch (OpenIDConnectClientException $e) {
            return Redirect::route('auth.oidc.login');
        }
        return Redirect::route('auth.login.view');
    }

    private function setupOIDC(): OpenIDConnectClient
    {
        $oidc = new OpenIDConnectClient(
            config('services.hydra.public'),
            config('services.oidc.main.client_id'),
            config('services.oidc.main.secret')
        );
        $oidc->addScope(['openid']);
        $oidc->setRedirectURL(route('auth.oidc.callback'));

        if (App::isLocal()) {
            $oidc->setVerifyHost(false);
            $oidc->setVerifyPeer(false);
            $oidc->providerConfigParam([
                "authorization_endpoint" => config('services.hydra.local_public')."/oauth2/auth",
                "token_endpoint" => config('services.hydra.public')."/oauth2/token",
                "jwks_uri" => config('services.hydra.public')."/.well-known/jwks.json",
            ]);
        }
        return $oidc;
    }

    /**
     * @throws \Jumbojett\OpenIDConnectClientException
     */
    public function login(): void
    {
        $oidc = $this->setupOIDC();
        $oidc->authenticate();
    }
}
