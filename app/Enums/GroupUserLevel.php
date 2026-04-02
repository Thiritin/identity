<?php

namespace App\Enums;

enum GroupUserLevel: string
{
    case Member = 'member';
    case DivisionDirector = 'division_director';
    case Director = 'director';
    case TeamLead = 'team_lead';

    public function isLeadRole(): bool
    {
        return in_array($this, [
            self::DivisionDirector,
            self::Director,
            self::TeamLead,
        ], true);
    }

    public function canManageAcl(): bool
    {
        return in_array($this, [
            self::DivisionDirector,
            self::Director,
            self::TeamLead,
        ], true);
    }

    public static function leadOrManagerLevels(): array
    {
        return [
            self::DivisionDirector,
            self::Director,
            self::TeamLead,
        ];
    }
}
