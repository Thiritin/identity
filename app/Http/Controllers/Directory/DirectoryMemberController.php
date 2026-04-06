<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Directory\StoreMemberRequest;
use App\Http\Requests\Directory\UpdateMemberRequest;
use App\Models\Group;
use App\Models\User;
use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Http\RedirectResponse;

class DirectoryMemberController extends Controller
{
    public function store(StoreMemberRequest $request, Group $group): RedirectResponse
    {
        $user = User::where('hashid', $request->validated('user_hashid'))->firstOrFail();

        if ($group->users()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['user_hashid' => 'User is already a member of this group.']);
        }

        $group->users()->attach($user, [
            'level' => GroupUserLevel::from($request->validated('level')),
            'title' => $request->validated('title'),
            'can_manage_members' => $request->boolean('can_manage_members'),
        ]);

        return back();
    }

    public function update(UpdateMemberRequest $request, Group $group, User $user): RedirectResponse
    {
        $group->users()->updateExistingPivot($user->id, $request->validated());

        return back();
    }

    public function destroy(Group $group, User $user): RedirectResponse
    {
        $authorizer = app(DirectoryAuthorizer::class);
        $viewer = request()->user();

        if (! $authorizer->canManageMembers($viewer, $group)) {
            abort(403);
        }

        // Viewers can only remove members whose level they could assign
        if (! $authorizer->hasGlobalPowers($viewer)) {
            $targetPivot = $group->users()->where('user_id', $user->id)->first()?->pivot;
            if ($targetPivot) {
                $targetLevel = $targetPivot->level instanceof GroupUserLevel
                    ? $targetPivot->level
                    : GroupUserLevel::from($targetPivot->level);
                $assignable = $authorizer->assignableLevels($viewer, $group);
                if (! in_array($targetLevel, $assignable, true)) {
                    abort(403);
                }
            }
        }

        $group->users()->detach($user);

        return back();
    }
}
