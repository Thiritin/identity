<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Hydra;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->missing('logout_challenge')) {
            \Auth::logout();
            return \Redirect::route('auth.login.view');
        }
        $request->validate(['logout_challenge' => 'required|string']);
        $hydra = new Hydra();
        $response = $hydra->acceptLogoutRequest()->get('logout_challenge');
        return redirect($response->redirect_to);
    }
}
