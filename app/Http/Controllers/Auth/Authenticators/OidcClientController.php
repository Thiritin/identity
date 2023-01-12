<?php

namespace App\Http\Controllers\Auth\Authenticators;

use App\Http\Controllers\Controller;
use App\Services\OpenIDConnectClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Jumbojett\OpenIDConnectClientException;
use Vinkla\Hashids\Facades\Hashids;
use function abort;
use function config;
use function route;

class OidcClientController extends Controller
{
    /**
     * @throws OpenIDConnectClientException
     */
    public function callback(Request $request)
    {
        $oidc = $this->setupOIDC($request);
        try {
            if ($oidc->authenticate() === true) {
                if (!$oidc->verifyJWTsignature($oidc->getIdToken())) abort(403, 'ID Token invalid.');
                Auth::guard('web')->loginUsingId(Hashids::decode($oidc->getIdTokenPayload()->sub));
                Session::put('web.id_token', $oidc->getIdToken());
                Session::put('web.access_token', $oidc->getAccessToken());
                return Redirect::route('dashboard');
            }
        } catch (\App\Services\OpenIDConnectClientException $e) {
            return Redirect::route('auth.oidc.login');
        }
        return Redirect::route('auth.login.view');
    }

    private function setupOIDC(Request $request): OpenIDConnectClient
    {
        $oidc = new OpenIDConnectClient(
            config('services.hydra.public'),
            config('services.oidc.main.client_id'),
            config('services.oidc.main.secret'),
            null,
            $request
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
     * @throws OpenIDConnectClientException
     */
    public function login(Request $request): RedirectResponse
    {
        $oidc = $this->setupOIDC($request);
        $oidc->authenticate();
        return response()->redirectTo($oidc->laravelRedirectUrl);
    }
}
