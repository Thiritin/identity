<?php

namespace App\Http\Controllers;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Http\Requests\GroupUpdateRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
        $departments = Group::where('type', GroupTypeEnum::Department)->withCount('users')->get();
        $departments->map(function (Group $group) {
            if ($group->logo) {
                $group->logo = Storage::url('avatars/'.$group->logo);
            }
            return $group;
        });

        $myGroups = Auth::user()->groups()->withCount('users')->get();
        $myGroups->map(function (Group $group) {
            if ($group->logo) {
                $group->logo = Storage::url('avatars/'.$group->logo);
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
        $members = [];
        if ($group->type !== GroupTypeEnum::Automated) {
            $members = $group->users()->get()
                ->map(fn(User $user) => [
                    "hashid" => $user->hashid,
                    "name" => $user->name,
                    "title" => $user->pivot->title,
                    "level" => $user->pivot->level,
                    "avatar" => (is_null($user->profile_photo_path)) ? null : Storage::url('avatars/'.$user->profile_photo_path),
                ])->sortByDesc(fn($data) => match ($data['level']->value) {
                    GroupUserLevel::Member->value => 2,
                    GroupUserLevel::Moderator->value => 3,
                    GroupUserLevel::Admin->value => 4,
                    GroupUserLevel::Owner->value => 5
                })->values()->all();
        }
        /**
         * Only allowed to view if is member of that group
         */
        return Inertia::render('Groups/View', [
            'group' => $group->only(['name', "hashid", 'description', 'type', 'logo_url']),
            'members' => $members,
            'canSeeSettings' => Gate::allows('update', $group),
        ]);
    }

    public function edit(Group $group)
    {
        /**
         * Only allowed to view if is member of that group
         */
        return Inertia::render('Groups/Edit', [
            'group' => $group,
        ]);
    }

    public function update(GroupUpdateRequest $request, Group $group)
    {

    }

    public function destroy(Group $group)
    {
    }
}
