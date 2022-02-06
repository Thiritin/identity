<?php

namespace App\Http\Middleware;

use App\Services\Hydra;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccessTokenValidationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->session()->get('access_token');
        if ($token === null) Auth::logout();

        $hydra = new Hydra();
        $response = $hydra->getToken($token, ['openid']);

        if ($response->active === false) {
            Auth::logout();
            return abort(401, "Unauthorized.");
        }
        return $next($request);
    }
}
