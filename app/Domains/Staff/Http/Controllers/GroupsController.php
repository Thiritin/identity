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
        $myDepartments = $request->user()->groups()
            ->where('type', GroupTypeEnum::Department)->select('id', 'level')->get()
            ->mapWithKeys(fn ($role) => [$role->id => ucwords($role->level)]);

        $departments = Group::where('type', GroupTypeEnum::Department)
            ->withCount('users')->get();

        $departmentsSortedByMembershipAndUserCount = $departments->sortByDesc(fn ($department) => [
            $myDepartments->contains($department->id),
            $department->users_count,
        ]);

        return Inertia::render('Staff/Groups/GroupsIndex', [
            'groups' => $departmentsSortedByMembershipAndUserCount->values(),
            'myGroups' => $myDepartments,
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
