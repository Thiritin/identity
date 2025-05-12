<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Hydra\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Redirect;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->session()->get('web.token') !== null) {
            Session::flush();

            return Redirect::to(config('app.url') . '/oauth2/sessions/logout');
        }

        if ($request->missing('logout_challenge')) {
            Auth::logout();
            Session::flush();

            return Redirect::to(config('app.url') . '/oauth2/sessions/logout');
        }

        $request->validate(['logout_challenge' => 'required|string']);
        $hydra = new Client();

        $hydraResponse = $hydra->acceptLogoutRequest($request->get('logout_challenge'));

        return redirect($hydraResponse['redirect_to']);
    }
}
