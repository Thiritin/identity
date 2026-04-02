<?php

namespace App\Services;

use App\Enums\TwoFactorTypeEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BackupCodeService
{
    private const CODE_COUNT = 8;

    private const CODE_LENGTH = 8;

    /**
     * Generate an array of plaintext backup codes.
     *
     * @return string[]
     */
    public function generate(): array
    {
        $codes = [];
        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $codes[] = strtoupper(Str::random(self::CODE_LENGTH));
        }

        return $codes;
    }

    /**
     * Store backup codes for a user, replacing any existing ones.
     *
     * @param  string[]  $plaintextCodes
     */
    public function storeForUser(User $user, array $plaintextCodes): void
    {
        // Soft-delete existing backup codes
        $user->twoFactors()->where('type', TwoFactorTypeEnum::BackupCodes)->delete();

        $hashedCodes = array_map(fn (string $code) => Hash::make($code), $plaintextCodes);

        $user->twoFactors()->create([
            'type' => TwoFactorTypeEnum::BackupCodes,
            'secret' => json_encode($hashedCodes),
        ]);
    }

    /**
     * Verify a backup code and consume it if valid.
     */
    public function verify(User $user, string $code): bool
    {
        $record = $user->twoFactors()->where('type', TwoFactorTypeEnum::BackupCodes)->first();

        if (! $record) {
            return false;
        }

        $code = $this->normalize($code);
        $hashedCodes = json_decode($record->secret, true);

        if (! is_array($hashedCodes)) {
            return false;
        }

        foreach ($hashedCodes as $index => $hashedCode) {
            if (Hash::check($code, $hashedCode)) {
                unset($hashedCodes[$index]);
                $record->update([
                    'secret' => json_encode(array_values($hashedCodes)),
                    'last_used_at' => now(),
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Get the number of remaining backup codes.
     */
    public function remainingCount(User $user): int
    {
        $record = $user->twoFactors()->where('type', TwoFactorTypeEnum::BackupCodes)->first();

        if (! $record) {
            return 0;
        }

        $hashedCodes = json_decode($record->secret, true);

        return is_array($hashedCodes) ? count($hashedCodes) : 0;
    }

    /**
     * Check if the user has backup codes.
     */
    public function hasBackupCodes(User $user): bool
    {
        return $user->twoFactors()->where('type', TwoFactorTypeEnum::BackupCodes)->exists();
    }

    /**
     * Format a code for display (e.g., "A1B2C3D4" → "A1B2-C3D4").
     */
    public static function formatForDisplay(string $code): string
    {
        return substr($code, 0, 4) . '-' . substr($code, 4);
    }

    /**
     * Strip dashes and spaces, uppercase.
     */
    private function normalize(string $code): string
    {
        return strtoupper(str_replace(['-', ' '], '', $code));
    }
}
