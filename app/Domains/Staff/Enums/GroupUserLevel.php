<?php

namespace App\Domains\Staff\Enums;

enum GroupUserLevel: string
{
    case Member = 'member';
    case TeamLead = 'team_lead';
    case Director = 'director';
    case DivisionDirector = 'division_director';

    public function getDisplayName(): string
    {
        return match($this) {
            self::Member => 'Member',
            self::TeamLead => 'Team Lead',
            self::Director => 'Director',
            self::DivisionDirector => 'Division Director',
        };
    }

    public function getLevel(): int
    {
        return match($this) {
            self::Member => 1,
            self::TeamLead => 2,
            self::Director => 3,
            self::DivisionDirector => 4,
        };
    }

    public function isLeadership(): bool
    {
        return in_array($this, [self::TeamLead, self::Director, self::DivisionDirector]);
    }
}
