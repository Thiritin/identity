<?php

namespace App\Http\Controllers\Auth\Authenticators;

use App\Http\Controllers\Controller;
use App\Services\Hydra\Client;
use App\Services\OpenIDConnectClient;
use App\Services\OpenIDService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use UnexpectedValueException;
use Vinkla\Hashids\Facades\Hashids;

class OidcClientController extends Controller
{
    private OpenIDService $openIDService;

    public function __construct()
    {
        $this->openIDService = new OpenIDService();
    }

    public function callback(Request $request)
    {
        $data = $request->validate([
            "state"             => "required_with::code|string",
            "error"             => "nullable|required_without:code|string",
            "error_description" => "nullable|required_without:code|string",
            "code"              => "nullable|string",
        ]);
        /**
         * Only Identity Client - Redirects to error page if scope is invalid
         */
        if (isset($data['error'])) {
            return Redirect::route('auth.error', [
                'error'             => $data['error'],
                'error_description' => $data['error_description'],
            ]);
        }

        /**
         * State Verification
         * Do not delete the default "false" parameter of Session::get
         * otherwise null === null and it would pass the check falsely.
         */
        if ($request->get('state') !== Session::get('web.login.oauth2state', false)) {
            Session::remove("web.login.oauth2state");
            return Redirect::route('auth.error', [
                'error'             => "invalid_state",
                'error_description' => "We we're unable to verify the state of your client. Please try to go back and login again.",
            ]);
        }

        /**
         * Get Tokens
         */
        $provider = $this->openIDService->setupOIDC($request, $this->clientIsAdmin($request));
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $data['code'],
        ]);

        $hydra = new Client();
        $token = $hydra->getToken($accessToken->getToken(), ['openid', 'profile']);
        if (!isset($token['sub'])) {
            throw new UnexpectedValueException("Could not request user id from freshly fetched token.");
        }

        $guard = ($this->clientIsAdmin($request)) ? "admin" : "web";

        $userid = Hashids::connection('user')->decode($token['sub'])[0];
        Auth::guard($guard)->loginUsingId($userid);
        Session::put('token', $accessToken);
        return $this->redirectDestination($request);
    }

    public function login(Request $request): RedirectResponse
    {
        $provider = $this->openIDService->setupOIDC($request, $this->clientIsAdmin($request));
        $authorizationUrl = $provider->getAuthorizationUrl();
        Session::put('web.login.oauth2state', $provider->getState());
        return Redirect::to($authorizationUrl);
    }

    public function clientIsAdmin(Request $request)
    {
        return $request->routeIs('filament.*');
    }

    private function redirectDestination(Request $request)
    {
        if ($this->clientIsAdmin($request)) {
            return Redirect::route('filament.pages.dashboard');
        }
        return Redirect::route('dashboard');
    }
}
