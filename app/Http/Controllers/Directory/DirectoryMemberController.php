<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Directory\StoreMemberRequest;
use App\Http\Requests\Directory\UpdateMemberRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DirectoryMemberController extends Controller
{
    public function store(StoreMemberRequest $request, Group $group): RedirectResponse
    {
        $user = User::where('hashid', $request->validated('user_hashid'))->firstOrFail();

        if ($group->users()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['user_hashid' => 'User is already a member of this group.']);
        }

        $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

        return back();
    }

    public function update(UpdateMemberRequest $request, Group $group, User $user): RedirectResponse
    {
        $group->users()->updateExistingPivot($user->id, $request->validated());

        return back();
    }

    public function destroy(Group $group, User $user): RedirectResponse
    {
        $this->authorize('update', $group);

        $group->users()->detach($user);

        return back();
    }
}
