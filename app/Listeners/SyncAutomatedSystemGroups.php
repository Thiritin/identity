<?php

namespace App\Listeners;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Events\GroupUserUpdated;
use App\Models\Group;
use App\Models\User;

class SyncAutomatedSystemGroups
{
    /**
     * Map of system group name => closure returning true when the user qualifies.
     */
    private const SYSTEM_GROUPS = [
        'staff' => [self::class, 'qualifiesForStaff'],
        'team_leads' => [self::class, 'qualifiesForTeamLeads'],
        'directors' => [self::class, 'qualifiesForDirectors'],
        'division_directors' => [self::class, 'qualifiesForDivisionDirectors'],
    ];

    public function handle(GroupUserAdded|GroupUserUpdated|GroupUserRemoved $event): void
    {
        $sourceGroup = $event->groupUser->group;

        // Only react to events on hierarchy groups — not the automated system
        // groups we manage, nor unrelated Default/Root groups.
        if (! in_array($sourceGroup->type, [
            GroupTypeEnum::Division,
            GroupTypeEnum::Department,
            GroupTypeEnum::Team,
        ], true)) {
            return;
        }

        $user = $event->groupUser->user;

        if (! $user) {
            return;
        }

        foreach (self::SYSTEM_GROUPS as $systemName => $qualifier) {
            $group = Group::where('system_name', $systemName)->first();

            if (! $group) {
                continue;
            }

            if ($qualifier($user)) {
                $group->users()->syncWithoutDetaching([
                    $user->id => ['level' => GroupUserLevel::Member],
                ]);
            } else {
                $group->users()->detach($user->id);
            }
        }
    }

    private static function qualifiesForStaff(User $user): bool
    {
        return $user->groups()
            ->where('type', GroupTypeEnum::Department)
            ->exists();
    }

    private static function qualifiesForTeamLeads(User $user): bool
    {
        return $user->groups()
            ->where('type', GroupTypeEnum::Team)
            ->wherePivot('level', GroupUserLevel::TeamLead->value)
            ->exists();
    }

    private static function qualifiesForDirectors(User $user): bool
    {
        return $user->groups()
            ->where('type', GroupTypeEnum::Department)
            ->wherePivot('level', GroupUserLevel::Director->value)
            ->exists();
    }

    private static function qualifiesForDivisionDirectors(User $user): bool
    {
        return $user->groups()
            ->where('type', GroupTypeEnum::Division)
            ->wherePivot('level', GroupUserLevel::DivisionDirector->value)
            ->exists();
    }
}
