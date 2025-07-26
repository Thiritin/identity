<?php

namespace App\Domains\Staff\Http\Controllers;

use App\Domains\Staff\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Domains\User\Models\Group;
use App\Domains\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class GroupMemberController extends Controller
{
    public function index(Group $group, Request $request)
    {
        return Inertia::render('Staff/Groups/Tabs/MemberTab', [
            'group' => $group->loadCount('users')->only(['hashid', 'name', 'users_count', 'parent_id']),
            'parent' => $group->parent?->only(['hashid', 'name']),
            // Sort Director, TeamLead, Member and then by name
            'users' => $group->users()
                ->with([
                    'groups' => fn ($q) => $q->where('type', 'team')->where('parent_id', $group->id)->get(['id', 'name']),
                ])
                ->withPivot(['level', 'title', 'can_manage_users'])->get(['id', 'name', 'profile_photo_path'])->map(fn ($user
                ) => [
                    'id' => $user->hashid,
                    'name' => $user->name,
                    'profile_photo_path' => (is_null($user->profile_photo_path)) ? null : Storage::drive('s3-avatars')->url($user->profile_photo_path),
                    'level' => $user->pivot->level->value,
                    'can_manage_users' => $user->pivot->can_manage_users,
                    'teams' => $user->groups->map(fn ($group) => [
                        'id' => $group->hashid,
                        'name' => $group->name,
                    ]),
                    'title' => $user->pivot->title,
                ])->sortBy(fn ($user) => [
                    $user['level'] === 'DivisionDirector' ? 0 : 1,
                    $user['level'] === 'Director' ? 1 : 2,
                    $user['level'] === 'TeamLead' ? 2 : 3,
                    $user['name'],
                ]),
            'canEdit' => $request->user()->can('update', $group),
        ]);
    }

    public function store(Group $group, Request $request)
    {
        $request->validate([
            'email' => 'required_without:user_id|nullable|email|exists:users,email',
            'user_id' => 'required_without:email|nullable|exists:users,id',
        ]);
        // look up by email
        if ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
            $field = 'email';
        } else {
            $user = User::find($request->user_id);
            $field = 'user_id';
        }
        // If null
        if (is_null($user)) {
            throw ValidationException::withMessages([$field => 'User not found']);
        }
        // If email not verified throw validationexception
        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([$field => 'User has not verified their email']);
        }
        // If user is already in the department throw validationexception
        if ($group->users->contains($user)) {
            throw ValidationException::withMessages([$field => 'User is already in the group']);
        }
        // Attach user to department
        $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
        // If group has a parent, add them to the parent group
        if ($group->parent && ! $group->parent->users->contains($user)) {
            $group->parent->users()->syncWithoutDetaching([$user->id => ['level' => GroupUserLevel::Member]]);
        }

        return redirect()->route('staff.groups.members.index', $group);
    }

    public function edit(Group $group, User $member)
    {
        return Inertia::render('Staff/GroupMember/GroupMemberEdit', [
            'group' => $group,
            // Load pivot data
            'member' => $group->users()->where('user_id', $member->id)->select(['id', 'name'])->first(),
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Group $group, User $member, Request $request)
    {
        $canManageFromParent = $group->parent?->userCanManageUsers($request->user());
        if ($member->id == $request->user()->id && ! $canManageFromParent) {
            throw ValidationException::withMessages(['You cannot update your own level.']);
        }

        $data = $request->validate([
            'level' => new Enum(GroupUserLevel::class),
        ]);

        $requestMember = $group->users()->find($member->id)->pivot;
        $this->authorize('update', $requestMember);

        $pivot = $group->users()->find($member->id)->pivot;
        $pivot->update($data);

        return to_route('staff.groups.members.index', ['group' => $group->hashid()]);
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function destroy(Group $group, User $member, Request $request)
    {
        if ($member->id === $request->user()->id && ! $group->parent?->userCanManageUsers($request->user())) {
            throw ValidationException::withMessages(['You cannot remove yourself.']);
        }

        $requestMember = $group->users()->find($member)->pivot;
        $this->authorize('delete', $requestMember);
        $group->users()->detach($member);
        // If group has children, remove them from the children
        if ($group->children()->exists()) {
            $group->children->each(fn ($child) => $child->users()->detach($member));
        }
    }
}
