<?php

namespace App\Policies;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;

class GroupPolicy
{
    use HandlesAuthorization;

    private const STAFF_VIEWABLE_TYPES = [
        GroupTypeEnum::Root,
        GroupTypeEnum::Division,
        GroupTypeEnum::Department,
        GroupTypeEnum::Team,
    ];

    /**
     * View Any will be limited on the controller level to a users own groups or staff groups
     */
    public function viewAny(User $user): bool|Response
    {
        if (Auth::guard('web')->check()) {
            return $user->can('admin.groups.view');
        }
        if (Auth::guard('web')->check()) {
            return true;
        }
        if (Auth::guard('api')->check()) {
            return $user->scopeCheck('groups.read');
        }

        // Handle Sanctum token authentication (auth:sanctum middleware)
        // When using Sanctum, no specific guard is active, so we check for API scopes
        if (Auth::check() && $user->currentAccessToken()) {
            if (! $user->scopeCheck('groups.read')) {
                return Response::deny('Insufficient permissions, groups.read is missing');
            }

            return true;
        }

        return true;
    }

    public function view(User $user, Group $group): Response
    {
        $inGroup = $user->inGroup($group->id);
        $isStaff = $user->isStaff();
        $staffException = in_array($group->type, self::STAFF_VIEWABLE_TYPES, true) && $isStaff;
        $userPermission = $user->scopeCheck('groups.read');

        if ($inGroup || $staffException) {
            if ($userPermission === false) {
                return Response::deny('Insufficient permissions, groups.read is missing');
            }

            return Response::allow();
        }

        // Provide specific error messages based on the situation
        if (! $inGroup && in_array($group->type, self::STAFF_VIEWABLE_TYPES, true)) {
            $staffGroup = Group::where('system_name', 'staff')->first();

            if (! $staffGroup) {
                return Response::deny('Staff group is not configured. Please contact administrator.');
            }

            if (! $isStaff) {
                return Response::deny('You must be a staff member to access this department/team.');
            }
        }

        return Response::deny('User is not a member of the group');
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
        $userManagerInGroup = GroupUser::whereUserId($user->id)
            ->whereGroupId($group->id)
            ->where(function ($query) {
                $query
                    ->where('can_manage_members', true)
                    ->orWhereIn('level', array_map(fn (GroupUserLevel $level) => $level->value, GroupUserLevel::leadOrManagerLevels()));
            })
            ->exists();
        $userManagerInParentGroup = GroupUser::whereUserId($user->id)
            ->whereGroupId($group->parent_id)
            ->where(function ($query) {
                $query
                    ->where('can_manage_members', true)
                    ->orWhereIn('level', array_map(fn (GroupUserLevel $level) => $level->value, GroupUserLevel::leadOrManagerLevels()));
            })
            ->exists();

        return (Auth::guard('web')->check() && $user->can('admin.groups.update')) || (($userManagerInGroup || $userManagerInParentGroup) && $user->scopeCheck('groups.update'));
    }

    public function delete(User $user, Group $group): bool
    {
        if ($group->type !== GroupTypeEnum::Team) {
            return false;
        }
        $userManagerInGroup = GroupUser::whereUserId($user->id)
            ->whereGroupId($group->id)
            ->where(function ($query) {
                $query
                    ->where('can_manage_members', true)
                    ->orWhereIn('level', array_map(fn (GroupUserLevel $level) => $level->value, GroupUserLevel::leadOrManagerLevels()));
            })
            ->exists();
        $userManagerInParentGroup = GroupUser::whereUserId($user->id)
            ->whereGroupId($group->parent_id)
            ->where(function ($query) {
                $query
                    ->where('can_manage_members', true)
                    ->orWhereIn('level', array_map(fn (GroupUserLevel $level) => $level->value, GroupUserLevel::leadOrManagerLevels()));
            })
            ->exists();

        return (Auth::guard('web')->check() && $user->can('admin.groups.delete')) || (($userManagerInGroup || $userManagerInParentGroup) && $user->scopeCheck('groups.delete'));
    }
}
