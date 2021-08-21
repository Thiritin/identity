<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\HydraServiceProvider;
use App\Services\Hydra;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Jumbojett\OpenIDConnectClient;
use Vinkla\Hashids\Facades\Hashids;

class LoginController extends Controller
{
    public function view(\Illuminate\Http\Request $request)
    {
        if ($request->missing('login_challenge')) {
            if (Auth::check()) {
                return Redirect::route('dashboard');
            }
            $this->useOwnOidcClient();
        }

        if (Auth::check()) {
            $this->acceptLogin($request->get('login_challenge'));
        }

        return Inertia::render('Auth/Login');
    }

    public function submit(LoginRequest $request)
    {
        $loginData = [
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ];
        if (!Auth::once($loginData)) {
            throw ValidationException::withMessages(['nouser' => 'Wrong details']);
        }
        return $this->acceptLogin($request->get('login_challenge'));
    }

    public function callback()
    {
        $oidc = $this->setupOIDC();
        if ($oidc->authenticate()) {
            if (!$oidc->verifyJWTsignature($oidc->getIdToken())) abort(403, 'ID Token invalid.');
            Auth::guard('web')->loginUsingId(Hashids::decode($oidc->getIdTokenPayload()->sub));
            Session::put('id_token', $oidc->getIdToken());
            return Redirect::route('dashboard');
        }
        return Redirect::route('auth.login.view');
    }

    private function useOwnOidcClient(): void
    {
        $oidc = $this->setupOIDC();
        $oidc->authenticate();
    }

    private function setupOIDC(): OpenIDConnectClient
    {
        $oidc = new OpenIDConnectClient(
            config('services.hydra.public'),
            config('services.oidc.main.client_id'),
            config('services.oidc.main.secret')
        );
        $oidc->addScope(['openid']);
        $oidc->setRedirectURL(route('auth.login.callback'));

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

    private function acceptLogin(string $login_challenge): \Illuminate\Http\Response
    {
        $hydra = new Hydra();
        $hydraResponse = $hydra->acceptLoginRequest(Auth::user()->getHashId(), $login_challenge);
        abort_if(empty($hydraResponse->redirect_to), 500);

        return Inertia::location($hydraResponse->redirect_to);
    }
}
