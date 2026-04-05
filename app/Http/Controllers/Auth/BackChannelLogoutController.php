<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OauthSession;
use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackChannelLogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $logoutToken = $request->input('logout_token');

        if (empty($logoutToken)) {
            return response('Missing logout_token', 400);
        }

        try {
            $claims = $this->verifyAndDecode($logoutToken);
        } catch (\Exception $e) {
            Log::error('Backchannel logout token verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response('Invalid logout_token', 400);
        }

        if (! $this->validateClaims($claims)) {
            return response('Invalid claims', 400);
        }

        $sid = $claims->sid;

        $oauthSession = OauthSession::where('session_id', $sid)->first();

        if (isset($claims->sub) && $oauthSession) {
            $user = User::where('hashid', $claims->sub)->first();
            if (! $user || $user->id !== $oauthSession->user_id) {
                Log::warning('Backchannel logout sub mismatch', [
                    'sid' => $sid,
                    'token_sub' => $claims->sub,
                    'session_user_id' => $oauthSession->user_id,
                ]);

                return response('Subject mismatch', 400);
            }
        }

        DB::table('sessions')->where('hydra_sid', $sid)->delete();

        if ($oauthSession) {
            $user = User::find($oauthSession->user_id);
            if ($user) {
                $user->forceFill(['remember_token' => null])->save();
            }
            $oauthSession->delete();
        }

        Log::info('Backchannel logout processed', [
            'sid' => $sid,
            'user_id' => $oauthSession?->user_id,
        ]);

        return response('', 200);
    }

    private function verifyAndDecode(string $token): \stdClass
    {
        $jwks = $this->getJwks();

        try {
            return JWT::decode($token, JWK::parseKeySet($jwks));
        } catch (SignatureInvalidException $e) {
            try {
                $jwks = $this->getJwks(forceRefresh: true);
            } catch (\Throwable) {
                throw $e;
            }

            return JWT::decode($token, JWK::parseKeySet($jwks));
        }
    }

    private function getJwks(bool $forceRefresh = false): array
    {
        $cacheKey = 'hydra_jwks';
        $cooldownKey = 'hydra_jwks_refresh_cooldown';

        if ($forceRefresh && ! Cache::has($cooldownKey)) {
            Cache::forget($cacheKey);
            Cache::put($cooldownKey, true, now()->addMinutes(5));
        }

        return Cache::remember($cacheKey, now()->addHour(), function () {
            $discoveryUrl = config('services.apps.identity.openid_configuration');
            $config = Cache::remember('identity_config', now()->addDay(), function () use ($discoveryUrl) {
                return Http::get($discoveryUrl)->throw()->json();
            });

            return Http::get($config['jwks_uri'])->throw()->json();
        });
    }

    private function validateClaims(\stdClass $claims): bool
    {
        $validIssuers = array_unique(array_filter([
            config('services.hydra.public'),
            rtrim(config('services.hydra.public'), '/') . '/',
            config('app.url'),
            rtrim(config('app.url'), '/') . '/',
        ]));
        if (! isset($claims->iss) || ! in_array($claims->iss, $validIssuers)) {
            Log::warning('Backchannel logout invalid issuer', ['iss' => $claims->iss ?? null]);

            return false;
        }

        $knownAudiences = array_filter([
            config('services.apps.identity.client_id'),
        ]);
        $tokenAud = is_array($claims->aud) ? $claims->aud : [$claims->aud];
        if ($knownAudiences && ! array_intersect($tokenAud, $knownAudiences)) {
            Log::info('Backchannel logout from unrecognized client', ['aud' => $claims->aud]);
        }

        $backchannelEvent = 'http://schemas.openid.net/event/backchannel-logout';
        if (! isset($claims->events->$backchannelEvent)) {
            Log::warning('Backchannel logout missing events claim');

            return false;
        }

        if (isset($claims->nonce)) {
            Log::warning('Backchannel logout token contains nonce');

            return false;
        }

        if (! isset($claims->iat) || abs(time() - $claims->iat) > 300) {
            Log::warning('Backchannel logout iat out of range', ['iat' => $claims->iat ?? null]);

            return false;
        }

        if (! isset($claims->sid)) {
            Log::warning('Backchannel logout missing sid');

            return false;
        }

        if (isset($claims->jti)) {
            $jtiKey = 'bclogout_jti:' . $claims->jti;
            if (Cache::has($jtiKey)) {
                Log::warning('Backchannel logout replay detected', ['jti' => $claims->jti]);

                return false;
            }
            Cache::put($jtiKey, true, now()->addMinutes(10));
        }

        return true;
    }
}
