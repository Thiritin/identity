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

    public function create(User $user, GroupUser $groupUser): bool
    {
        return $user->scopeCheck('groups.update') && $groupUser->isAdmin();
    }

    public function delete(User $user, GroupUser $groupUser): Response
    {
        if ($groupUser->level === GroupUserLevel::Owner) {
            return Response::deny("Owners cannot be removed from group.");
        }
        if($user->scopeCheck('groups.update') && $groupUser->isAdmin()) {
            return Response::allow();
        }
        return Response::deny('Insufficient permissions, you cannot delete users.');
    }
}
