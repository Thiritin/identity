<?php

namespace App\Policies;

use App\Enums\GroupUserLevel;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class GroupUserPolicy
{
    use HandlesAuthorization;

    public function view(User $user, GroupUser $groupUser): bool
    {
        return $user->scopeCheck('groups.read') && ($groupUser->isMember() || $groupUser->group->parent?->isMember($user));
    }

    public function update(User $user, GroupUser $groupUser): bool
    {
        return $user->scopeCheck('groups.update') && ($groupUser->isAdmin() || $groupUser->group->isAdmin($user) || $groupUser->group->parent?->isAdmin($user));
    }

    public function create(User $user, GroupUser $groupUser): bool
    {
        return $user->scopeCheck('groups.update') && ($groupUser->isAdmin() || $groupUser->group->isAdmin($user) || $groupUser->group->parent?->isAdmin($user));
    }

    public function delete(User $user, GroupUser $groupUser): Response
    {
        if ($groupUser->level === GroupUserLevel::Owner) {
            return Response::deny('Owners cannot be removed from group.');
        }
        if ($user->scopeCheck('groups.update') && $groupUser->isAdmin()) {
            return Response::allow();
        }

        // check if user is of type admin
        if ($groupUser->group->isAdmin($user)) {
            return Response::allow();
        }

        // check if user is admin of parent group
        if ($groupUser->group->parent && $groupUser->group->parent->isAdmin($user)) {
            return Response::allow();
        }

        return Response::deny('Insufficient permissions, you cannot delete users.');
    }
}
