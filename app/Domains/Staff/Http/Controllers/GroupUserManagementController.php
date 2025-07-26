<?php

namespace App\Domains\Staff\Http\Controllers;

use App\Domains\Staff\Models\Group;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GroupUserManagementController extends Controller
{
    /**
     * Show the user management interface for a group
     */
    public function index(Group $group)
    {
        $currentUser = Auth::guard('staff')->user();
        
        // Check if current user can manage users in this group
        if (!$group->userCanManageUsers($currentUser)) {
            abort(403, 'You do not have permission to manage users in this group.');
        }

        $currentUserGroupUser = $group->users()->where('user_id', $currentUser->id)->first();

        $users = $group->users()
            ->withPivot(['level', 'title', 'can_manage_users'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->pivot->id,
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'avatar_url' => $user->avatar_url,
                    ],
                    'level' => $user->pivot->level->value,
                    'can_manage_users' => $user->pivot->can_manage_users,
                    'global_rank' => $this->getUserGlobalRank($user),
                ];
            });

        $userPermissions = [
            'userId' => $currentUser->id,
            'globalRank' => $this->getUserGlobalRank($currentUser),
            'teamLevel' => $currentUserGroupUser ? $currentUserGroupUser->pivot->level->value : 'Member',
            'canManageUsers' => $currentUserGroupUser ? $currentUserGroupUser->pivot->can_manage_users : false,
        ];

        return Inertia::render('Groups/ManageUsers', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'type' => $group->type->value,
            ],
            'users' => $users,
            'userPermissions' => $userPermissions,
        ]);
    }

    /**
     * Determine the global rank of a user
     */
    private function getUserGlobalRank(User $user): string
    {
        // Check if user is a director in any group
        $isDirector = $user->groups()
            ->withPivot('level')
            ->get()
            ->contains(function ($group) {
                return in_array($group->pivot->level->value, ['Director', 'DivisionDirector']);
            });

        return $isDirector ? 'Director' : 'Staff';
    }

    /**
     * Grant user management permission to a user
     */
    public function grantUserManagement(Group $group, User $user)
    {
        $currentUser = Auth::guard('staff')->user();
        
        // Check if current user can manage users in this group
        if (!$group->userCanManageUsers($currentUser)) {
            abort(403, 'You do not have permission to manage users in this group.');
        }

        // Check if target user is a member of the group
        if (!$group->isMember($user)) {
            abort(404, 'User is not a member of this group.');
        }

        // Update the pivot record
        $group->users()->updateExistingPivot($user->id, [
            'can_manage_users' => true
        ]);

        return back()->with('success', "User management permission granted to {$user->name}.");
    }

    /**
     * Revoke user management permission from a user
     */
    public function revokeUserManagement(Group $group, User $user)
    {
        $currentUser = Auth::guard('staff')->user();
        
        // Check if current user can manage users in this group
        if (!$group->userCanManageUsers($currentUser)) {
            abort(403, 'You do not have permission to manage users in this group.');
        }

        // Check if target user is a member of the group
        if (!$group->isMember($user)) {
            abort(404, 'User is not a member of this group.');
        }

        // Don't allow revoking from directors (they have inherent rights)
        $member = $group->users->find($user);
        if ($member && $member->pivot->isDirector()) {
            abort(422, 'Cannot revoke user management rights from directors.');
        }

        // Update the pivot record
        $group->users()->updateExistingPivot($user->id, [
            'can_manage_users' => false
        ]);

        return back()->with('success', "User management permission revoked from {$user->name}.");
    }

    /**
     * Update user's level in the group
     */
    public function updateUserLevel(Group $group, User $user, Request $request)
    {
        $currentUser = Auth::guard('staff')->user();
        
        // Check if current user can manage users in this group
        if (!$group->userCanManageUsers($currentUser)) {
            abort(403, 'You do not have permission to manage users in this group.');
        }

        $request->validate([
            'level' => 'required|in:member,team_lead,director,division_director',
            'title' => 'nullable|string|max:255',
        ]);

        // Validate level against group type
        $this->validateLevelForGroupType($group, $request->level);

        // Update the pivot record
        $updateData = [
            'level' => $request->level,
            'title' => $request->title,
        ];

        // If promoting to director level, grant user management rights automatically
        if (in_array($request->level, ['director', 'division_director'])) {
            $updateData['can_manage_users'] = true;
        }

        $group->users()->updateExistingPivot($user->id, $updateData);

        return back()->with('success', "Updated {$user->name}'s role in the group.");
    }

    /**
     * Validate that the level is appropriate for the group type
     */
    private function validateLevelForGroupType(Group $group, string $level): void
    {
        $validLevels = match ($group->type->value) {
            'bod' => ['member'],
            'division' => ['member', 'division_director'],
            'department' => ['member', 'director'],
            'team' => ['member', 'team_lead'],
            default => ['member'],
        };

        if (!in_array($level, $validLevels)) {
            abort(422, "Level '{$level}' is not valid for {$group->type->getDisplayName()}.");
        }
    }
}
