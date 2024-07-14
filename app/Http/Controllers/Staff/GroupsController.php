<?php

namespace App\Http\Controllers\Staff;

use App\Enums\GroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\GroupUpdateRequest;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Parsedown;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Stevebauman\Purify\Facades\Purify;

class GroupsController extends Controller
{
    public function index(Request $request)
    {
        $myDepartments = $request->user()->groups()
            ->where('type', GroupTypeEnum::Department)->select('id', 'level')->get()
            ->mapWithKeys(fn($role) => [$role->id => ucwords($role->level)]);

        $departments = Group::where('type', GroupTypeEnum::Department)
            ->withCount('users')->get();
        $departmentsSortedByMembershipAndUserCount = $departments->sortByDesc(fn($department) => [
            $myDepartments->contains($department->id),
            $department->users_count
        ]);

        return Inertia::render('Staff/Groups/GroupsIndex', [
            'groups' => $departmentsSortedByMembershipAndUserCount,
            'myGroups' => $myDepartments,
        ]);
    }

    public function show(Group $group, Request $request)
    {
        $Parsedown = new Parsedown();
        $Parsedown->setSafeMode(true);
        return Inertia::render('Staff/Groups/Tabs/GroupInfoTab', [
            'group' => $group->loadCount('users')->only(['hashid', 'description', 'name', 'users_count']),
            'descriptionHtml' => $Parsedown->parse($group->description),
            'canEdit' => $group->isAdmin($request->user()),
        ]);
    }

    public function update(GroupUpdateRequest $request, Group $group)
    {
        Gate::authorize('update', $group);
        $group->update($request->validated());
        return redirect()->back();

        $group->update([
            'description' => Purify::clean($request->validated()['description'], [
                'HTML.Allowed' => 'div,p,span,ul,ol,li,strong,em,br,a[href],img[src],h1,h2,h3,h4,h5,h6'
            ])
        ]);
        return redirect()->back();
    }
}
