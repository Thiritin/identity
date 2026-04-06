<?php

namespace App\Support\Directory;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;

final class DirectoryAuthorizer
{
    public function hasGlobalPowers(User $viewer): bool
    {
        return $viewer->is_admin || $viewer->is_hr;
    }

    public function effectiveLevel(User $viewer, Group $group): ?GroupUserLevel
    {
        $membership = $viewer->groups()->where('groups.id', $group->id)->first();

        if (! $membership) {
            return null;
        }

        $level = $membership->pivot->level instanceof GroupUserLevel
            ? $membership->pivot->level
            : GroupUserLevel::from($membership->pivot->level);

        if ($level->isLeadRole()) {
            return $level;
        }

        if ($membership->pivot->can_manage_members
            && $group->type !== GroupTypeEnum::Division
            && $group->type->topLeadLevel()) {
            return $group->type->topLeadLevel();
        }

        return null;
    }

    public function assignableLevels(User $viewer, Group $group): array
    {
        $typeAllowed = $group->type->allowedLevels();

        if ($this->hasGlobalPowers($viewer)) {
            return $typeAllowed ?: GroupUserLevel::cases();
        }

        $levels = collect();

        $selfLevel = $this->effectiveLevel($viewer, $group);
        if ($selfLevel) {
            $levels = $levels->merge($selfLevel->assignableLevels());
        }

        if ($group->parent_id) {
            $group->loadMissing('parent');
            $parentLevel = $this->effectiveLevel($viewer, $group->parent);
            if ($parentLevel) {
                $levels = $levels->merge($parentLevel->assignableLevels());
            }
        }

        if ($typeAllowed) {
            $levels = $levels->filter(fn ($l) => in_array($l, $typeAllowed, true));
        }

        return $levels->unique()->values()->all();
    }

    public function canManageMembers(User $viewer, Group $group): bool
    {
        if ($this->hasGlobalPowers($viewer)) {
            return true;
        }

        if ($this->effectiveLevel($viewer, $group) !== null) {
            return true;
        }

        if ($group->parent_id) {
            $group->loadMissing('parent');

            return $this->effectiveLevel($viewer, $group->parent) !== null;
        }

        return false;
    }

    public function canCreateChildGroup(User $viewer, Group $parent): bool
    {
        if ($parent->type->childGroupType() === null) {
            return false;
        }

        if ($this->hasGlobalPowers($viewer)) {
            return true;
        }

        return $this->effectiveLevel($viewer, $parent) !== null;
    }

    public function levelAllowedByType(GroupUserLevel $level, Group $group): bool
    {
        $allowed = $group->type->allowedLevels();

        return $allowed === [] || in_array($level, $allowed, true);
    }
}
