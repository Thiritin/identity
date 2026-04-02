<?php

use App\Enums\TwoFactorTypeEnum;
use App\Models\User;
use App\Services\WebAuthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new WebAuthnService();
    $this->user = User::factory()->create();
});

it('detects cross-purpose credential duplicate', function () {
    $credentialId = base64_encode(random_bytes(32));

    $this->user->twoFactors()->create([
        'type' => TwoFactorTypeEnum::PASSKEY,
        'name' => 'My Passkey',
        'credential_id' => $credentialId,
        'public_key' => json_encode(['test' => true]),
        'sign_count' => 0,
        'transports' => ['internal'],
        'aaguid' => fake()->uuid(),
    ]);

    expect($this->service->credentialExistsForOtherPurpose(
        $this->user,
        $credentialId,
        TwoFactorTypeEnum::SECURITY_KEY,
    ))->toBeTrue();
});

it('does not flag same-purpose as cross-purpose duplicate', function () {
    $credentialId = base64_encode(random_bytes(32));

    $this->user->twoFactors()->create([
        'type' => TwoFactorTypeEnum::PASSKEY,
        'name' => 'My Passkey',
        'credential_id' => $credentialId,
        'public_key' => json_encode(['test' => true]),
        'sign_count' => 0,
        'transports' => ['internal'],
        'aaguid' => fake()->uuid(),
    ]);

    expect($this->service->credentialExistsForOtherPurpose(
        $this->user,
        $credentialId,
        TwoFactorTypeEnum::PASSKEY,
    ))->toBeFalse();
});

it('generates registration options with correct authenticator selection for passkey', function () {
    $options = $this->service->generateRegistrationOptions($this->user, TwoFactorTypeEnum::PASSKEY);

    expect($options)
        ->toHaveKey('rp')
        ->toHaveKey('user')
        ->toHaveKey('challenge')
        ->toHaveKey('pubKeyCredParams')
        ->toHaveKey('authenticatorSelection');

    expect($options['authenticatorSelection']['residentKey'])->toBe('required');
    expect($options['authenticatorSelection']['userVerification'])->toBe('required');

    // Verify options were cached
    $cacheKey = "webauthn-register-{$this->user->id}-passkey";
    expect(Cache::has($cacheKey))->toBeTrue();
});

it('generates registration options with correct authenticator selection for security key', function () {
    $options = $this->service->generateRegistrationOptions($this->user, TwoFactorTypeEnum::SECURITY_KEY);

    expect($options['authenticatorSelection']['residentKey'])->toBe('discouraged');
    expect($options['authenticatorSelection']['userVerification'])->toBe('discouraged');

    $cacheKey = "webauthn-register-{$this->user->id}-security_key";
    expect(Cache::has($cacheKey))->toBeTrue();
});

it('excludes existing credentials from registration options', function () {
    $this->user->twoFactors()->create([
        'type' => TwoFactorTypeEnum::PASSKEY,
        'name' => 'Existing Passkey',
        'credential_id' => 'dGVzdC1jcmVkZW50aWFs',
        'public_key' => json_encode(['test' => true]),
        'sign_count' => 0,
        'transports' => ['internal'],
        'aaguid' => fake()->uuid(),
    ]);

    $this->user->twoFactors()->create([
        'type' => TwoFactorTypeEnum::SECURITY_KEY,
        'name' => 'Existing Key',
        'credential_id' => 'c2Vjb25kLWNyZWRlbnRpYWw',
        'public_key' => json_encode(['test' => true]),
        'sign_count' => 0,
        'transports' => ['usb'],
        'aaguid' => fake()->uuid(),
    ]);

    $options = $this->service->generateRegistrationOptions($this->user, TwoFactorTypeEnum::PASSKEY);

    expect($options)->toHaveKey('excludeCredentials');
    expect($options['excludeCredentials'])->toHaveCount(2);
});

it('generates authentication options with correct allow credentials', function () {
    $this->user->twoFactors()->create([
        'type' => TwoFactorTypeEnum::PASSKEY,
        'name' => 'My Passkey',
        'credential_id' => 'dGVzdC1jcmVkZW50aWFs',
        'public_key' => json_encode(['test' => true]),
        'sign_count' => 0,
        'transports' => ['internal'],
        'aaguid' => fake()->uuid(),
    ]);

    // Security key should NOT appear in passkey auth options
    $this->user->twoFactors()->create([
        'type' => TwoFactorTypeEnum::SECURITY_KEY,
        'name' => 'My Key',
        'credential_id' => 'c2Vjb25kLWNyZWRlbnRpYWw',
        'public_key' => json_encode(['test' => true]),
        'sign_count' => 0,
        'transports' => ['usb'],
        'aaguid' => fake()->uuid(),
    ]);

    $options = $this->service->generateAuthenticationOptions($this->user, TwoFactorTypeEnum::PASSKEY);

    expect($options)
        ->toHaveKey('challenge')
        ->toHaveKey('rpId')
        ->toHaveKey('allowCredentials');

    expect($options['allowCredentials'])->toHaveCount(1);
    expect($options['userVerification'])->toBe('required');

    $cacheKey = "webauthn-auth-{$this->user->id}-passkey";
    expect(Cache::has($cacheKey))->toBeTrue();
});
