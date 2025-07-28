<?php

namespace App\Domains\Auth\Services\WebAuthn;

use App\Domains\Auth\Services\SecurityNotificationService;
use App\Domains\User\Models\User;
use App\Domains\User\Models\WebauthnCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptionsBuilder;
use Webauthn\PublicKeyCredentialRequestOptionsBuilder;

class WebAuthnService
{
    private const CHALLENGE_CACHE_TTL = 300; // 5 minutes

    public function generateRegistrationOptions(User $user): PublicKeyCredentialCreationOptions
    {
        $challenge = random_bytes(32);
        $challengeKey = 'webauthn_registration_challenge_' . $user->id;
        
        Cache::put($challengeKey, base64_encode($challenge), self::CHALLENGE_CACHE_TTL);

        $rpEntity = new PublicKeyCredentialRpEntity(
            config('app.name'),
            parse_url(config('app.url'), PHP_URL_HOST)
        );

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->email,
            (string) $user->id,
            $user->name
        );

        $credentialParameters = [
            new PublicKeyCredentialParameters('public-key', -7), // ES256
            new PublicKeyCredentialParameters('public-key', -257), // RS256
        ];

        // Exclude existing credentials
        $excludeCredentials = $user->webauthnCredentials->map(function ($credential) {
            return new PublicKeyCredentialDescriptor(
                'public-key',
                base64_decode($credential->credential_id)
            );
        })->toArray();

        $authenticatorSelection = new AuthenticatorSelectionCriteria(
            null, // authenticatorAttachment
            true, // requireResidentKey
            'preferred' // userVerification
        );

        return (new PublicKeyCredentialCreationOptionsBuilder($rpEntity, $userEntity, $challenge))
            ->allowCredentials(...$credentialParameters)
            ->excludeCredentials(...$excludeCredentials)
            ->setAuthenticatorSelection($authenticatorSelection)
            ->build();
    }

    public function generateAuthenticationOptions(?User $user = null): PublicKeyCredentialRequestOptions
    {
        $challenge = random_bytes(32);
        $challengeKey = $user ? 
            'webauthn_auth_challenge_' . $user->id :
            'webauthn_auth_challenge_' . Str::random(10);
        
        Cache::put($challengeKey, base64_encode($challenge), self::CHALLENGE_CACHE_TTL);

        $builder = new PublicKeyCredentialRequestOptionsBuilder($challenge);

        if ($user && $user->webauthnCredentials()->exists()) {
            $allowCredentials = $user->webauthnCredentials->map(function ($credential) {
                return new PublicKeyCredentialDescriptor(
                    'public-key',
                    base64_decode($credential->credential_id),
                    $credential->transports ?? []
                );
            })->toArray();

            $builder->allowCredentials(...$allowCredentials);
        }

        return $builder
            ->setUserVerification('preferred')
            ->setTimeout(60000) // 60 seconds
            ->build();
    }

    public function verifyRegistration(
        User $user,
        array $attestationResponse,
        PublicKeyCredentialCreationOptions $options
    ): ?WebauthnCredential {
        try {
            $challengeKey = 'webauthn_registration_challenge_' . $user->id;
            $storedChallenge = Cache::get($challengeKey);
            
            if (!$storedChallenge || base64_decode($storedChallenge) !== $options->getChallenge()) {
                return null;
            }

            // Basic validation - in production, use a proper WebAuthn library
            $credentialId = base64_encode($attestationResponse['rawId']);
            
            // Check if credential already exists
            if (WebauthnCredential::where('credential_id', $credentialId)->exists()) {
                return null;
            }

            $credential = WebauthnCredential::create([
                'user_id' => $user->id,
                'credential_id' => $credentialId,
                'public_key' => base64_encode($attestationResponse['response']['publicKey']),
                'attestation_object' => base64_encode($attestationResponse['response']['attestationObject']),
                'aaguid' => $attestationResponse['aaguid'] ?? null,
                'name' => $attestationResponse['name'] ?? 'Security Key',
                'transports' => $attestationResponse['transports'] ?? [],
            ]);

            Cache::forget($challengeKey);
            
            return $credential;

        } catch (\Exception $e) {
            \Log::error('WebAuthn registration failed: ' . $e->getMessage());
            return null;
        }
    }

    public function verifyAuthentication(
        array $assertionResponse,
        PublicKeyCredentialRequestOptions $options,
        ?User $user = null
    ): ?User {
        try {
            $credentialId = base64_encode($assertionResponse['rawId']);
            
            $credential = WebauthnCredential::where('credential_id', $credentialId)->first();
            
            if (!$credential) {
                return null;
            }

            // If user is provided, ensure the credential belongs to them
            if ($user && $credential->user_id !== $user->id) {
                return null;
            }

            $challengeKey = $user ? 
                'webauthn_auth_challenge_' . $user->id :
                'webauthn_auth_challenge_*';
                
            // Basic challenge verification
            $storedChallenge = null;
            if ($user) {
                $storedChallenge = Cache::get('webauthn_auth_challenge_' . $user->id);
            } else {
                // For usernameless flow, we'd need to store challenge differently
                // This is a simplified implementation
            }

            if (!$storedChallenge || base64_decode($storedChallenge) !== $options->getChallenge()) {
                return null;
            }

            // Mark credential as used
            $credential->markAsUsed();
            
            // Notify of passkey usage
            app(SecurityNotificationService::class)->notifyPasskeyUsed(
                $credential->user,
                $credential->device_name,
                request()->userAgent(),
                request()->ip()
            );
            
            // Clear challenge
            if ($user) {
                Cache::forget('webauthn_auth_challenge_' . $user->id);
            }

            return $credential->user;

        } catch (\Exception $e) {
            \Log::error('WebAuthn authentication failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getChallengeKey(User $user, string $type = 'auth'): string
    {
        return "webauthn_{$type}_challenge_{$user->id}";
    }

    public function clearChallenge(string $challengeKey): void
    {
        Cache::forget($challengeKey);
    }
}