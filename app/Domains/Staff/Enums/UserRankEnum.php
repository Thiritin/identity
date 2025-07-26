<?php

namespace App\Domains\Staff\Enums;

enum UserRankEnum: string
{
    case Staffer = 'staffer';
    case Director = 'director';

    public function getDisplayName(): string
    {
        return match($this) {
            self::Staffer => 'Staffer',
            self::Director => 'Director',
        };
    }

    public function getLevel(): int
    {
        return match($this) {
            self::Staffer => 1,
            self::Director => 2,
        };
    }
}
