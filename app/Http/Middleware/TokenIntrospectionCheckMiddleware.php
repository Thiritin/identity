<?php

namespace App\Http\Middleware;

use App\Services\Hydra\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Redirect;

/**
 * Additional verification on sensitive endpoints.
 */
class TokenIntrospectionCheckMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $isAdmin = $request->routeIs('filament.*');
        $prefix = ($isAdmin) ? 'admin' : 'web';
        // Get Token that is saved in session.
        $token = $request->session()->get($prefix . '.token');

        $response = Cache::remember('introspected-token-' . hash('xxh3', $token->getToken()), now()->addSeconds(60), function () use ($token) {
            $hydra = new Client();

            return $hydra->getToken($token, ['openid']);
        });

        if ($response['active'] !== true) {
            /**
             * Access Token is not active anymore. Security check failed
             * Logout user and reauthenticate.
             */
            Auth::logout();

            return Redirect::route('auth.oidc.login');
        }

        return $next($request);
    }
}
