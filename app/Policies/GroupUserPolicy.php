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
        return ($user->scopeCheck('groups.read') && $groupUser->isMember());
    }

    public function update(User $user, GroupUser $groupUserInitiator): bool
    {
        return $user->scopeCheck('groups.update') && $groupUserInitiator->isAdmin();
    }

    public function create(User $user, GroupUser $groupUserInitiator): bool
    {
        return $user->scopeCheck('groups.update') && $groupUserInitiator->isAdmin();
    }

    public function delete(User $user, GroupUser $groupUser): Response
    {
        if ($groupUser->level === GroupUserLevel::Owner) {
            return Response::deny("Owners cannot be removed from group.");
        }
        if ($user->scopeCheck('groups.update') && $groupUser->isAdmin()) {
            return Response::allow();
        }

        // check if user is of type admin
        if ($groupUser->group->isAdmin($user)) {
            return Response::allow();
        }
        return Response::deny('Insufficient permissions, you cannot delete users.');
    }
}
