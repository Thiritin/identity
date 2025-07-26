<?php

namespace App\Domains\Staff\Http\Controllers;

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Domains\User\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GroupTeamController extends Controller
{
    public function index(Group $group, Request $request)
    {
        Gate::authorize('view', $group);
        $myDepartments = $request->user()->groups()
            ->where('type', GroupTypeEnum::Department)->select('id', 'level')->get()
            ->mapWithKeys(fn ($role) => [$role->id => ucwords($role->level)]);

        return Inertia::render('Staff/Groups/Tabs/TeamTab', [
            'group' => $group->loadCount('users')->only(['hashid', 'name', 'users_count', 'parent_id']),
            'parent' => $group->parent?->only(['hashid', 'name']),
            'teams' => $group->children()
                ->where('type', GroupTypeEnum::Team)
                ->withCount('users')
                ->get(['hashid', 'name', 'users_count']),
            'myGroups' => $myDepartments->values(),
            'canEdit' => $group->isAdmin($request->user()),
        ]);
    }

    public function store(Group $group, Request $request)
    {
        Gate::authorize('update', $group);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $group->children()->create([
            'name' => $validated['name'],
            'type' => GroupTypeEnum::Team,
        ]);

        return redirect()->back();
    }
}
