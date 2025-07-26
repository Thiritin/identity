<?php

namespace App\Domains\Staff\Enums;

enum GroupTypeEnum: string
{
    case Default = 'none';
    case BOD = 'bod';
    case Division = 'division';
    case Department = 'department';
    case Team = 'team';
    case Automated = 'automated';

    public function getDisplayName(): string
    {
        return match($this) {
            self::Default => 'Default',
            self::BOD => 'Board of Directors',
            self::Division => 'Division',
            self::Department => 'Department',
            self::Team => 'Team',
            self::Automated => 'Automated',
        };
    }

    public function getLevel(): int
    {
        return match($this) {
            self::BOD => 1,
            self::Division => 2,
            self::Department => 3,
            self::Team => 4,
            self::Default => 5,
            self::Automated => 6,
        };
    }

    public function canHaveParent(GroupTypeEnum $parentType): bool
    {
        return match($this) {
            self::BOD => false,
            self::Division => $parentType === self::BOD,
            self::Department => $parentType === self::Division,
            self::Team => in_array($parentType, [self::Department, self::Team]),
            self::Default => true,
            self::Automated => false,
        };
    }
}
