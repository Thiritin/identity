<?php

namespace App\Policies;

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('admin.groups.view');
    }

    public function view(User $user, Group $group): bool
    {
        $userInGroup = $user->groups()->whereId($group->id)->exists();
        return ($user->can('admin.groups.read') || ($userInGroup && $user->permCheck('groups.read')));
    }

    public function create(User $user): bool
    {
        return $user->can('admin.groups.create');
    }

    public function update(User $user, Group $group): bool
    {
        $userAdminInGroup = $user->whereHas('groups', function ($q) use ($group) {
            $q->whereIn('level', [GroupUserLevel::Admin->value, GroupUserLevel::Owner->value])
              ->where('group_id', $group->id);
        })->exists();
        return ($user->can('admin.groups.update') || ($userAdminInGroup && $user->permCheck('groups.update')));
    }

    public function delete(User $user, Group $group): bool
    {
        $userOwnerInGroup = $user->whereHas('groups', function ($q) use ($group) {
            $q->where('level', GroupUserLevel::Owner->value)
              ->where('group_id', $group->id);
        })->exists();
        return ($user->can('admin.groups.delete') || ($userOwnerInGroup && $user->permCheck('groups.delete')));
    }
}
