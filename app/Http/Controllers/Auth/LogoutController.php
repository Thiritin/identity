<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Hydra\Client;
use Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->missing('logout_challenge')) {
            Auth::logout();
            return Inertia::location(route('auth.choose'));
        }

        $request->validate(['logout_challenge' => 'required|string']);
        $hydra = new Client();

        $hydraResponse = $hydra->acceptLogoutRequest($request->get('logout_challenge'));
        return redirect($hydraResponse["redirect_to"]);
    }
}
