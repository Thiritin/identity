<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\OpenIDConnectClient;
use App\Services\OpenIDConnectClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Vinkla\Hashids\Facades\Hashids;

class LoginController extends Controller
{
    /**
     * Initiate OAUTH Session
     */
    public function __invoke(Request $request)
    {

        $oidc = new OpenIDConnectClient(
            config('services.hydra.public'),
            config('services.oidc.admin.client_id'),
            config('services.oidc.admin.secret'),
            null,
            $request
        );
        $oidc->addScope(['openid']);
        /**
         * Only for development
         */
        if (App::isLocal()) {
            $oidc->setVerifyHost(false);
            $oidc->setVerifyPeer(false);
            $oidc->providerConfigParam([
                "authorization_endpoint" => config('services.hydra.local_public') . "/oauth2/auth",
                "token_endpoint" => config('services.hydra.public') . "/oauth2/token",
                "jwks_uri" => config('services.hydra.public') . "/.well-known/jwks.json",
            ]);
        }

        $oidc->setRedirectURL(route('filament.auth.callback'));
        try {
            if ($oidc->authenticate()) {
                $idToken = $oidc->getIdToken();
                Session::put('admin.id_token', $idToken);

                if (!$oidc->verifyJWTsignature($idToken)) abort(403, 'ID Token invalid.');
                Auth::guard('admin')->loginUsingId(Hashids::connection('user')->decode($oidc->getIdTokenPayload()->sub));
                return redirect(route('filament.pages.dashboard'));
            }
            return redirect($oidc->laravelRedirectUrl);
        } catch (OpenIDConnectClientException $e) {
            return Redirect::route('auth.oidc.login');
        }
    }
}
