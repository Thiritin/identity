<?php

namespace App\Policies;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class GroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('admin.groups.view');
    }

    public function view(User $user, Group $group): Response
    {
        $inGroup = $user->inGroup($group->id);
        $staffException = $group->type === GroupTypeEnum::Department && $user->isStaff();
        $userPermission = $user->scopeCheck('groups.read');

        if ($inGroup || $staffException) {
            if ($userPermission === false) {
                return Response::deny('Insufficient permissions, groups.read is missing');
            }
            return Response::allow();
        }
        return Response::deny("User is not a member of the group");
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
        return ($user->can('admin.groups.update') || ($userAdminInGroup && $user->scopeCheck('groups.update')));
    }

    public function delete(User $user, Group $group): bool
    {
        $userOwnerInGroup = $user->whereHas('groups', function ($q) use ($group) {
            $q->where('level', GroupUserLevel::Owner->value)
                ->where('group_id', $group->id);
        })->exists();
        return ($user->can('admin.groups.delete') || ($userOwnerInGroup && $user->scopeCheck('groups.delete')));
    }
}
