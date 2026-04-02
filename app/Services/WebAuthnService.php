<?php

namespace App\Services;

use App\Enums\TwoFactorTypeEnum;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class WebAuthnService
{
    private const CACHE_TTL_SECONDS = 300;

    private ?SerializerInterface $serializer = null;

    private ?CeremonyStepManagerFactory $ceremonyStepManagerFactory = null;

    private function getSerializer(): SerializerInterface
    {
        if ($this->serializer === null) {
            $attestationStatementSupportManager = AttestationStatementSupportManager::create();
            $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());

            $this->serializer = (new WebauthnSerializerFactory($attestationStatementSupportManager))->create();
        }

        return $this->serializer;
    }

    private function getCeremonyStepManagerFactory(): CeremonyStepManagerFactory
    {
        if ($this->ceremonyStepManagerFactory === null) {
            $this->ceremonyStepManagerFactory = new CeremonyStepManagerFactory();
        }

        return $this->ceremonyStepManagerFactory;
    }

    private function rpEntity(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(
            name: config('app.name'),
            id: parse_url(config('app.url'), PHP_URL_HOST),
        );
    }

    private function userEntity(User $user): PublicKeyCredentialUserEntity
    {
        return PublicKeyCredentialUserEntity::create(
            name: $user->email,
            id: $user->hashid,
            displayName: $user->name,
        );
    }

    private function host(): string
    {
        return parse_url(config('app.url'), PHP_URL_HOST);
    }

    /**
     * Generate WebAuthn registration options for the frontend.
     *
     * @return array<string, mixed>
     */
    public function generateRegistrationOptions(User $user, TwoFactorTypeEnum $purpose): array
    {
        $challenge = random_bytes(32);

        $excludeCredentials = $this->getExistingCredentialDescriptors($user);

        $authenticatorSelection = $purpose === TwoFactorTypeEnum::PASSKEY
            ? AuthenticatorSelectionCriteria::create(
                authenticatorAttachment: null,
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            )
            : AuthenticatorSelectionCriteria::create(
                authenticatorAttachment: null,
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED,
            );

        $options = PublicKeyCredentialCreationOptions::create(
            rp: $this->rpEntity(),
            user: $this->userEntity($user),
            challenge: $challenge,
            pubKeyCredParams: [
                PublicKeyCredentialParameters::createPk(-7),
                PublicKeyCredentialParameters::createPk(-257),
            ],
            authenticatorSelection: $authenticatorSelection,
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $excludeCredentials,
            timeout: 60000,
        );

        $cacheKey = $this->registrationCacheKey($user, $purpose);
        Cache::put($cacheKey, serialize($options), self::CACHE_TTL_SECONDS);

        return json_decode(
            $this->getSerializer()->serialize($options, 'json'),
            true,
        );
    }

    /**
     * Verify a WebAuthn registration response from the browser.
     */
    public function verifyRegistration(User $user, string $credentialJson, TwoFactorTypeEnum $purpose, string $name): TwoFactor
    {
        $cacheKey = $this->registrationCacheKey($user, $purpose);
        $cachedOptions = Cache::pull($cacheKey);

        if ($cachedOptions === null) {
            throw new \RuntimeException('Registration options not found or expired.');
        }

        $creationOptions = unserialize($cachedOptions);

        $publicKeyCredential = $this->getSerializer()->deserialize($credentialJson, PublicKeyCredential::class, 'json');
        $attestationResponse = $publicKeyCredential->response;

        if (! $attestationResponse instanceof AuthenticatorAttestationResponse) {
            throw new \RuntimeException('Invalid attestation response.');
        }

        $ceremonyStepManager = $this->getCeremonyStepManagerFactory()->creationCeremony();
        $validator = AuthenticatorAttestationResponseValidator::create(
            ceremonyStepManager: $ceremonyStepManager,
        );

        $source = $validator->check($attestationResponse, $creationOptions, $this->host());

        $credentialId = Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId);

        if ($this->credentialExistsForOtherPurpose($user, $credentialId, $purpose)) {
            throw new \RuntimeException('This credential is already registered for a different purpose.');
        }

        return $user->twoFactors()->create([
            'type' => $purpose,
            'name' => $name,
            'credential_id' => $credentialId,
            'public_key' => $this->getSerializer()->serialize($source, 'json'),
            'sign_count' => $source->counter,
            'transports' => $source->transports,
            'aaguid' => $source->aaguid->toString(),
        ]);
    }

    /**
     * Generate WebAuthn authentication options for the frontend.
     *
     * @return array<string, mixed>
     */
    public function generateAuthenticationOptions(User $user, TwoFactorTypeEnum $purpose): array
    {
        $challenge = random_bytes(32);

        $allowCredentials = $this->getCredentialDescriptorsForPurpose($user, $purpose);

        $userVerification = $purpose === TwoFactorTypeEnum::PASSKEY
            ? PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED
            : PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;

        $options = PublicKeyCredentialRequestOptions::create(
            challenge: $challenge,
            rpId: $this->host(),
            allowCredentials: $allowCredentials,
            userVerification: $userVerification,
            timeout: 60000,
        );

        $cacheKey = $this->authenticationCacheKey($user, $purpose);
        Cache::put($cacheKey, serialize($options), self::CACHE_TTL_SECONDS);

        return json_decode(
            $this->getSerializer()->serialize($options, 'json'),
            true,
        );
    }

    /**
     * Verify a WebAuthn authentication response from the browser.
     */
    public function verifyAuthentication(User $user, string $credentialJson, TwoFactorTypeEnum $purpose): TwoFactor
    {
        $cacheKey = $this->authenticationCacheKey($user, $purpose);
        $cachedOptions = Cache::pull($cacheKey);

        if ($cachedOptions === null) {
            throw new \RuntimeException('Authentication options not found or expired.');
        }

        $requestOptions = unserialize($cachedOptions);

        $publicKeyCredential = $this->getSerializer()->deserialize($credentialJson, PublicKeyCredential::class, 'json');
        $assertionResponse = $publicKeyCredential->response;

        if (! $assertionResponse instanceof AuthenticatorAssertionResponse) {
            throw new \RuntimeException('Invalid assertion response.');
        }

        $credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredential->rawId);
        $twoFactor = $user->twoFactors()
            ->where('type', $purpose)
            ->where('credential_id', $credentialId)
            ->first();

        if (! $twoFactor) {
            throw new \RuntimeException('Credential not found.');
        }

        $existingSource = $this->getSerializer()->deserialize($twoFactor->public_key, PublicKeyCredentialSource::class, 'json');

        $ceremonyStepManager = $this->getCeremonyStepManagerFactory()->requestCeremony();
        $validator = AuthenticatorAssertionResponseValidator::create(
            ceremonyStepManager: $ceremonyStepManager,
        );

        $updatedSource = $validator->check(
            $existingSource,
            $assertionResponse,
            $requestOptions,
            $this->host(),
            $existingSource->userHandle,
        );

        if ($updatedSource->counter > 0 && $updatedSource->counter <= $twoFactor->sign_count) {
            Log::warning('WebAuthn sign count did not increase', [
                'user' => $user->hashid,
                'credential_id' => $credentialId,
                'stored_count' => $twoFactor->sign_count,
                'received_count' => $updatedSource->counter,
            ]);
        }

        $twoFactor->update([
            'sign_count' => $updatedSource->counter,
            'last_used_at' => now(),
        ]);

        return $twoFactor;
    }

    /**
     * Check if a credential ID exists for a different WebAuthn purpose than the current one.
     */
    public function credentialExistsForOtherPurpose(User $user, string $credentialId, TwoFactorTypeEnum $currentPurpose): bool
    {
        $otherPurpose = $currentPurpose === TwoFactorTypeEnum::PASSKEY
            ? TwoFactorTypeEnum::SECURITY_KEY
            : TwoFactorTypeEnum::PASSKEY;

        return $user->twoFactors()
            ->where('type', $otherPurpose)
            ->where('credential_id', $credentialId)
            ->exists();
    }

    /**
     * Get credential descriptors for all existing WebAuthn credentials of the user.
     *
     * @return PublicKeyCredentialDescriptor[]
     */
    private function getExistingCredentialDescriptors(User $user): array
    {
        return $user->twoFactors()
            ->whereIn('type', [TwoFactorTypeEnum::PASSKEY, TwoFactorTypeEnum::SECURITY_KEY])
            ->get()
            ->map(fn (TwoFactor $tf) => PublicKeyCredentialDescriptor::create(
                type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                id: Base64UrlSafe::decodeNoPadding($tf->credential_id),
                transports: $tf->transports ?? [],
            ))
            ->all();
    }

    /**
     * Get credential descriptors for a specific WebAuthn purpose.
     *
     * @return PublicKeyCredentialDescriptor[]
     */
    private function getCredentialDescriptorsForPurpose(User $user, TwoFactorTypeEnum $purpose): array
    {
        return $user->twoFactors()
            ->where('type', $purpose)
            ->get()
            ->map(fn (TwoFactor $tf) => PublicKeyCredentialDescriptor::create(
                type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                id: Base64UrlSafe::decodeNoPadding($tf->credential_id),
                transports: $tf->transports ?? [],
            ))
            ->all();
    }

    private function registrationCacheKey(User $user, TwoFactorTypeEnum $purpose): string
    {
        return "webauthn-register-{$user->id}-{$purpose->value}";
    }

    private function authenticationCacheKey(User $user, TwoFactorTypeEnum $purpose): string
    {
        return "webauthn-auth-{$user->id}-{$purpose->value}";
    }
}
