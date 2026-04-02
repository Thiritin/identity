<?php

namespace Database\Factories;

use App\Enums\TwoFactorTypeEnum;
use App\Models\TwoFactor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use ParagonIE\ConstantTime\Base64UrlSafe;

class TwoFactorFactory extends Factory
{
    protected $model = TwoFactor::class;

    public function definition(): array
    {
        return [
            'type' => TwoFactorTypeEnum::TOTP,
            'secret' => $this->faker->sha256,
        ];
    }

    public function totp(): static
    {
        return $this->state(fn () => [
            'type' => TwoFactorTypeEnum::TOTP,
            'secret' => $this->faker->sha256,
        ]);
    }

    public function yubikey(): static
    {
        return $this->state(fn () => [
            'type' => TwoFactorTypeEnum::YUBIKEY,
            'identifier' => substr($this->faker->sha256, 0, 12),
        ]);
    }

    public function passkey(): static
    {
        return $this->state(fn () => [
            'type' => TwoFactorTypeEnum::PASSKEY,
            'name' => $this->faker->word(),
            'credential_id' => Base64UrlSafe::encodeUnpadded(random_bytes(32)),
            'public_key' => json_encode(['type' => 'public-key', 'key' => base64_encode(random_bytes(65))]),
            'sign_count' => 0,
            'transports' => ['internal'],
            'aaguid' => $this->faker->uuid(),
        ]);
    }

    public function securityKey(): static
    {
        return $this->state(fn () => [
            'type' => TwoFactorTypeEnum::SECURITY_KEY,
            'name' => $this->faker->word(),
            'credential_id' => Base64UrlSafe::encodeUnpadded(random_bytes(32)),
            'public_key' => json_encode(['type' => 'public-key', 'key' => base64_encode(random_bytes(65))]),
            'sign_count' => 0,
            'transports' => ['usb'],
            'aaguid' => $this->faker->uuid(),
        ]);
    }

    public function backupCodes(array $plaintextCodes = []): static
    {
        if (empty($plaintextCodes)) {
            $plaintextCodes = [];
            for ($i = 0; $i < 8; $i++) {
                $plaintextCodes[] = strtoupper($this->faker->bothify('????????'));
            }
        }

        $hashedCodes = array_map(fn (string $code) => Hash::make($code), $plaintextCodes);

        return $this->state(fn () => [
            'type' => TwoFactorTypeEnum::BackupCodes,
            'secret' => json_encode($hashedCodes),
        ]);
    }
}
