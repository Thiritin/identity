<?php

namespace App\Enums;

enum GroupTypeEnum: string
{
    case Default = 'none';
    case Division = 'division';
    case Department = 'department';
    case Team = 'team';
    case Automated = 'automated';
    case Root = 'root';

    public function allowedLevels(): array
    {
        return match ($this) {
            self::Division   => [GroupUserLevel::DivisionDirector],
            self::Department => [GroupUserLevel::Director, GroupUserLevel::Member],
            self::Team       => [GroupUserLevel::TeamLead, GroupUserLevel::Member],
            default          => [],
        };
    }

    public function childGroupType(): ?self
    {
        return match ($this) {
            self::Division   => self::Department,
            self::Department => self::Team,
            default          => null,
        };
    }

    public function topLeadLevel(): ?GroupUserLevel
    {
        return match ($this) {
            self::Division   => GroupUserLevel::DivisionDirector,
            self::Department => GroupUserLevel::Director,
            self::Team       => GroupUserLevel::TeamLead,
            default          => null,
        };
    }
}
