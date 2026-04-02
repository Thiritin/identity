<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OauthSession;
use App\Services\Hydra\Client;
use App\Services\Hydra\HydraRequestException;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        $hydraSessionId = $request->session()->get('hydra_session_id');

        if ($request->session()->get('web.token') !== null) {
            Session::flush();

            if ($hydraSessionId) {
                OauthSession::where('session_id', $hydraSessionId)->delete();
            }

            return Redirect::to(config('app.url') . '/oauth2/sessions/logout');
        }

        if ($request->missing('logout_challenge')) {
            Auth::logout();
            Session::flush();

            if ($hydraSessionId) {
                OauthSession::where('session_id', $hydraSessionId)->delete();
            }

            return Redirect::to(config('app.url') . '/oauth2/sessions/logout');
        }

        $request->validate(['logout_challenge' => 'required|string']);

        try {
            $hydra = new Client();
            $hydraResponse = $hydra->acceptLogoutRequest($request->get('logout_challenge'));

            return redirect($hydraResponse['redirect_to']);
        } catch (HydraRequestException $e) {
            Log::warning('Logout challenge failed', [
                'message' => $e->getMessage(),
            ]);
            Auth::logout();
            Session::flush();

            if ($hydraSessionId) {
                OauthSession::where('session_id', $hydraSessionId)->delete();
            }

            return Redirect::route('auth.logged-out');
        }
    }
}
