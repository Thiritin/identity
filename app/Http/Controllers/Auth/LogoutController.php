<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Hydra;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->missing('logout_challenge')) {
            \Auth::logout();
            return Inertia::location(route('auth.login.view'));
        }

        $request->validate(['logout_challenge' => 'required|string']);
        $hydra = new Hydra();
        $response = $hydra->acceptLogoutRequest()->get('logout_challenge');
        return redirect($response->redirect_to);
    }
}
