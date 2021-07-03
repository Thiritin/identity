<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Jumbojett\OpenIDConnectClient;

class LoginController extends Controller
{
    /**
     * Initiate OAUTH Session
     */
    public function init()
    {
        $oidc = new OpenIDConnectClient(
            config('services.hydra.public'),
            config('services.oidc.admin.client_id'),
            config('services.oidc.admin.secret')
        );
        $oidc->addScope('openid','admin');
        /**
         * Only for development
         */
        if(App::isLocal()) {
            $oidc->setVerifyHost(false);
            $oidc->setVerifyPeer(false);
            $oidc->providerConfigParam([
                "authorization_endpoint" => config('services.hydra.local_public')."/oauth2/auth",
                "token_endpoint" => config('services.hydra.local_public')."/oauth2/token",
                "jwks_uri" => config('services.hydra.local_public')."/.well-known/jwks.json",
            ]);
        }
        $oidc->setRedirectURL(route('admin.login.callback'));
        $oidc->authenticate();
    }
}
