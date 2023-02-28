<?php

namespace App\Http\Controllers;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\App;
use App\Models\Group;
use App\Models\User;
use App\Policies\GroupPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GroupController extends Controller
{
    /**
     * @return \Inertia\Response
     * Shows the user a list the groups that he is part of.
     * It will also show other departments if the user is within the automated Staff Group.
     */
    public function index()
    {
        $departments = collect();
        // Check if user belongs to Staff
        if (Auth::user()->isStaff()) {
            $departments = Group::where('type', GroupTypeEnum::Department)->withCount('users')->get();
        }

        $myGroups = Auth::user()->groups()->where('level', '!=', GroupUserLevel::Invited)->withCount('users')->get();

        return Inertia::render('Groups/Index', [
            "myGroups" => $myGroups,
            "departments" => $departments
        ]);
    }

    public function create()
    {
    }

    public function store(Request $request)
    {
    }

    public function show(Group $group)
    {
        /**
         * Only allowed to view if is member of that group
         */
        $this->authorize('view', $group);

        return Inertia::render('Groups/View', [
            'group' => $group
        ]);
    }

    public function edit(Group $group)
    {
    }

    public function update(Request $request, Group $group)
    {
    }

    public function destroy(Group $group)
    {
    }
}
