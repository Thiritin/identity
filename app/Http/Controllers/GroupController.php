<?php

namespace App\Http\Controllers;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    /**
     * @return Response
     * Shows the user a list the groups that he is part of.
     * It will also show other departments if the user is within the automated Staff Group.
     */
    public function index()
    {
        $departments = collect();
        // Check if user belongs to Staff
        if (Auth::user()->isStaff()) {
            $departments = Group::where('type', GroupTypeEnum::Department)->withCount('users')->get();
            $departments->map(function (Group $group) {
                if ($group->logo) {
                    $group->logo = Storage::url('avatars/' . $group->logo);
                }
                return $group;
            });
        }

        $myGroups = Auth::user()->groups()->where('level', '!=', GroupUserLevel::Invited)->withCount('users')->get();
        $myGroups->map(function (Group $group) {
            if ($group->logo) {
                $group->logo = Storage::url('avatars/' . $group->logo);
            }
            return $group;
        });

        return Inertia::render('Groups/Index', [
            "myGroups" => $myGroups,
            "departments" => $departments,
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
            'group' => $group,
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
