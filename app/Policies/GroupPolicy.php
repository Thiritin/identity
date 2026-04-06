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

    public function create(User $user, ?Group $parent = null): bool
    {
        if ($user->is_admin || $user->is_hr) {
            return true;
        }

        if ($parent === null) {
            return false;
        }

        // Division Director can create Departments under their Division
        if ($parent->type === GroupTypeEnum::Division) {
            return GroupUser::where('user_id', $user->id)
                ->where('group_id', $parent->id)
                ->where('level', GroupUserLevel::DivisionDirector->value)
                ->exists();
        }

        // Director can create Teams under their Department
        if ($parent->type === GroupTypeEnum::Department) {
            return GroupUser::where('user_id', $user->id)
                ->where('group_id', $parent->id)
                ->where('level', GroupUserLevel::Director->value)
                ->exists();
        }

        return false;
    }

    public function update(User $user, Group $group): bool
    {
        if ($group->type === GroupTypeEnum::Automated) {
            return false;
        }

        if ($user->is_admin || $user->is_hr) {
            return true;
        }

        return $this->isManagerOfGroup($user, $group->id)
            || $this->isManagerOfGroup($user, $group->parent_id);
    }

    public function delete(User $user, Group $group): bool
    {
        if (in_array($group->type, [GroupTypeEnum::Automated, GroupTypeEnum::Root], true)) {
            return false;
        }

        if ($user->is_admin || $user->is_hr) {
            return true;
        }

        // Division Director can delete Departments in their Division
        if ($group->type === GroupTypeEnum::Department) {
            return $group->parent_id !== null
                && GroupUser::where('user_id', $user->id)
                    ->where('group_id', $group->parent_id)
                    ->where('level', GroupUserLevel::DivisionDirector->value)
                    ->exists();
        }

        // Director or TeamLead can delete Teams
        if ($group->type === GroupTypeEnum::Team) {
            return $this->isManagerOfGroup($user, $group->id)
                || $this->isManagerOfGroup($user, $group->parent_id);
        }

        return false;
    }

    private function isManagerOfGroup(User $user, ?int $groupId): bool
    {
        if ($groupId === null) {
            return false;
        }

        return GroupUser::where('user_id', $user->id)
            ->where('group_id', $groupId)
            ->where(function ($query) {
                $query
                    ->where('can_manage_members', true)
                    ->orWhereIn('level', array_map(
                        fn (GroupUserLevel $level) => $level->value,
                        GroupUserLevel::leadOrManagerLevels()
                    ));
            })
            ->exists();
    }
}
