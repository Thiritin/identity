<?php

namespace App\Services\Webhooks;

class UserFieldMap
{
    /**
     * External field name => users table column.
     * External names are what developers configure in the UI and what appears
     * in webhook payloads. DB columns are what Eloquent reports in getChanges().
     */
    public const MAP = [
        'email' => 'email',
        'username' => 'name',
    ];

    /** @return list<string> */
    public static function subscribableFields(): array
    {
        return array_keys(self::MAP);
    }

    public static function columnFor(string $externalField): ?string
    {
        return self::MAP[$externalField] ?? null;
    }

    public static function externalNameFor(string $column): ?string
    {
        $flipped = array_flip(self::MAP);
        return $flipped[$column] ?? null;
    }
}
