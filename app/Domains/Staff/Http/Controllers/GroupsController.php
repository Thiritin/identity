<?php

namespace App\Domains\Staff\Http\Controllers;

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\GroupUpdateRequest;
use App\Domains\Staff\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Parsedown;
use Stevebauman\Purify\Facades\Purify;

class GroupsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user's groups
        $myGroups = $user->groups()->with(['users' => function ($query) {
            $query->withPivot(['level', 'title']);
        }])->get()->map(function ($group) use ($user) {
            return [
                'id' => $group->id,
                'hashid' => $group->hashid,
                'name' => $group->name,
                'description' => $group->description,
                'type' => $group->type->getDisplayName(),
                'logo_url' => $group->logo_url,
                'members_count' => $group->users->count(),
                'user_level' => $group->pivot->level->getDisplayName(),
                'user_title' => $group->pivot->title,
                'can_manage' => $group->userCanManageUsers($user),
            ];
        });

        // Get all departments with leadership and teams
        $allDepartments = Group::where('type', GroupTypeEnum::Department)
            ->with([
                'users' => function ($query) {
                    $query->withPivot(['level', 'title'])
                          ->wherePivotIn('level', ['director', 'division_director']);
                },
                'children' => function ($query) {
                    $query->withCount('users');
                }
            ])
            ->withCount('users')
            ->get()
            ->map(function ($group) use ($user) {
                return [
                    'id' => $group->id,
                    'hashid' => $group->hashid,
                    'name' => $group->name,
                    'description' => $group->description,
                    'logo_url' => $group->logo_url,
                    'members_count' => $group->users_count,
                    'teams_count' => $group->children->count(),
                    'can_manage' => $group->userCanManageUsers($user),
                    'leadership' => $group->users->map(function ($leader) {
                        return [
                            'id' => $leader->id,
                            'name' => $leader->name,
                            'profile_photo_url' => $leader->profile_photo_url,
                            'level' => $leader->pivot->level->getDisplayName(),
                            'title' => $leader->pivot->title,
                        ];
                    }),
                    'teams' => $group->children->map(function ($team) {
                        return [
                            'id' => $team->id,
                            'name' => $team->name,
                            'description' => $team->description,
                            'members_count' => $team->users_count,
                        ];
                    }),
                ];
            });

        return Inertia::render('Staff/Groups/GroupsModern', [
            'myGroups' => $myGroups,
            'allDepartments' => $allDepartments,
        ]);
    }

    public function show(Group $group, Request $request)
    {
        $Parsedown = new Parsedown();
        $Parsedown->setSafeMode(true);

        return Inertia::render('Staff/Groups/Tabs/GroupInfoTab', [
            'group' => $group
                ->loadCount('users')
                ->only(['hashid', 'name', 'users_count', 'description', 'parent_id']),
            'parent' => $group->parent?->only(['hashid', 'name']),
            'descriptionHtml' => $Parsedown->parse($group->description),
            'canEdit' => $request->user()->can('update', $group),
        ]);
    }

    public function update(GroupUpdateRequest $request, Group $group)
    {
        Gate::authorize('update', $group);
        // if name isset then it needs to be not null
        if (isset($request->validated()['name'])) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);
        }
        $group->update($request->validated());

        return redirect()->back();

        $group->update([
            'description' => Purify::clean($request->validated()['description'], [
                'HTML.Allowed' => 'div,p,span,ul,ol,li,strong,em,br,a[href],img[src],h1,h2,h3,h4,h5,h6',
            ]),
        ]);

        return redirect()->back();
    }

    public function destroy(Group $group)
    {
        Gate::authorize('delete', $group);
        $parent = $group->parent;
        $group->delete();

        return redirect()->route('staff.groups.show', $parent);

    }
}
