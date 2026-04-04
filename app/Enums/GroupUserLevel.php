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

    /**
     * Returns the levels that this role is allowed to assign to other users.
     *
     * Hierarchy: Admin → DivisionDirector, DivisionDirector → Director, Director → TeamLead.
     * Everyone who can manage members can assign Member.
     */
    public function assignableLevels(): array
    {
        return match ($this) {
            self::DivisionDirector => [self::Director, self::TeamLead, self::Member],
            self::Director => [self::TeamLead, self::Member],
            self::TeamLead => [self::Member],
            self::Member => [],
        };
    }
}
