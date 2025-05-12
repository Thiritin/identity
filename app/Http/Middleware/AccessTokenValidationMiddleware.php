<?php

namespace App\Http\Middleware;

use App\Providers\Socialite\SocialiteIdentityProvider;
use App\Services\OpenIDService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Redirect;
use UnexpectedValueException;

class AccessTokenValidationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Determine Guard by Route
        $guard = match (true) {
            $request->routeIs('filament.*') => 'admin',
            $request->routeIs('staff.*') => 'staff',
            default => 'web',
        };
        $systemName = match ($guard) {
            'web' => 'portal',
            default => $guard,
        };
        /**
         * Skip for tests, as Hydra is not available during tests.
         */
        if (config('app.env') === 'testing') {
            return $next($request);
        }

        /** @var SocialiteIdentityProvider $provider */
        $provider = Socialite::driver('idp-' . $systemName);
        $token = $provider->getToken();
        $refreshToken = $provider->getRefreshToken();
        $tokenExpiresAt = $provider->getExpiresIn();

        /**
         * Logout if token does not exist
         */
        if ($token === null || $refreshToken === null || $tokenExpiresAt === null) {
            Auth::logout();

            return Redirect::route('auth.choose');
        }

        /**
         * If token expired, refresh using refresh token.
         */
        if ($tokenExpiresAt->isPast()) {
            $oidcService = (new OpenIDService())->setupOIDC($request, $systemName);
            try {
                $token = $oidcService->getAccessToken('refresh_token', [
                    'refresh_token' => $refreshToken,
                ]);
            } catch (IdentityProviderException | UnexpectedValueException $exception) {
                // If refresh fails, try to reauth user.
                Auth::logout();

                return Redirect::route('login.apps.redirect', ['app' => $systemName]);
            }
            $provider->putToken(
                token: $token->getToken(),
                refreshToken: $token->getRefreshToken(),
                expiresIn: Carbon::parse($token->getExpires()),
            );
        }

        return $next($request);
    }
}
