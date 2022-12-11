<?php

namespace App\Policies;

use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupUserPolicy
{
    use HandlesAuthorization;

    public function view(User $user, GroupUser $groupUser): bool
    {
        return ($user->permCheck('groups.read') && $groupUser->isMember());
    }

    public function create(User $user, GroupUser $groupUser): bool
    {
        return $user->permCheck('groups.update') && $groupUser->isAdmin();
    }

    public function delete(User $user, GroupUser $groupUser): bool
    {
        return $user->permCheck('groups.update') && $groupUser->isAdmin();
    }
}
