<?php

namespace App\Policies;

use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class GroupUserPolicy
{
    use HandlesAuthorization;

    private function userCanManageGroupMembers(User $user, GroupUser $groupUser): bool
    {
        $membership = GroupUser::whereUserId($user->id)
            ->whereGroupId($groupUser->group_id)
            ->first();

        if ($membership?->canManageMembers()) {
            return true;
        }

        if (! $groupUser->group?->parent_id) {
            return false;
        }

        $parentMembership = GroupUser::whereUserId($user->id)
            ->whereGroupId($groupUser->group->parent_id)
            ->first();

        return (bool) $parentMembership?->canManageMembers();
    }

    public function view(User $user, GroupUser $groupUser): bool
    {
        return $user->scopeCheck('groups.read') && ($groupUser->isMember() || $groupUser->group->parent?->isMember($user));
    }

    public function update(User $user, GroupUser $groupUser): bool
    {
        return $user->scopeCheck('groups.update') && $this->userCanManageGroupMembers($user, $groupUser);
    }

    public function create(User $user, GroupUser $groupUser): bool
    {
        return $user->scopeCheck('groups.update') && $this->userCanManageGroupMembers($user, $groupUser);
    }

    public function delete(User $user, GroupUser $groupUser): Response
    {
        if (! $user->scopeCheck('groups.update')) {
            return Response::deny('Insufficient permissions, groups.update is missing.');
        }

        if ($this->userCanManageGroupMembers($user, $groupUser)) {
            return Response::allow();
        }

        return Response::deny('Insufficient permissions, you cannot delete users.');
    }
}
