<?php

namespace App\Policies;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Auth;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * View Any will be limited on the controller level to a users own groups or staff groups
     */
    public function viewAny(User $user): bool
    {
        if (Auth::guard("admin")->check()) {
            return $user->can('admin.groups.view');
        }
        if (Auth::guard("web")->check()) {
            return true;
        }
        if (Auth::guard("api")->check()) {
            return $user->scopeCheck('groups.read');
        }
        return true;
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
        if ($group->type === GroupTypeEnum::Automated) {
            return false;
        }
        $userAdminInGroup = GroupUser::whereUserId($user->id)
            ->whereGroupId($group->id)
            ->where(fn($q) => $q->whereLevel(GroupUserLevel::Admin)->orWhere('level', GroupUserLevel::Owner))
            ->exists();
        return ((Auth::guard("admin")->check() && $user->can('admin.groups.update')) || ($userAdminInGroup && $user->scopeCheck('groups.update')));
    }

    public function delete(User $user, Group $group): bool
    {
        $userOwnerInGroup = GroupUser::whereUserId($user->id)->whereGroupId($group->id)->whereLevel(GroupUserLevel::Owner)->exists();
        return ((Auth::guard("admin")->check() && $user->can('admin.groups.delete')) || ($userOwnerInGroup && $user->scopeCheck('groups.delete')));
    }
}
