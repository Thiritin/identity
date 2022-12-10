<?php

namespace App\Http\Middleware;

use App\Services\Hydra\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AccessTokenValidationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        /**
         * Skip for tests, as Hydra is not available during tests.
         */
        if (config('app.env') === 'testing') {
            return $next($request);
        }
        // Get Token that is saved in session.
        $token = $request->session()->get('web.access_token');
        if ($token === null) {
            Auth::logout();
            return abort(401, "Unauthorized.");
        }

        // No need to call Hydra on every request.
        if (Cache::missing('accessToken.' . sha1($token) . '.validated')) {
            $hydra = new Client();
            $response = $hydra->getToken($token, ['openid']);
            if ($response['active'] === false) {
                Auth::logout();
                return abort(401, "Unauthorized.");
            }
            Cache::put('accessToken.' . sha1($token) . '.validated', true, now()->addSeconds(120));
        }

        return $next($request);
    }
}
