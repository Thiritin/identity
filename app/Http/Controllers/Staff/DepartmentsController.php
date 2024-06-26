<?php

namespace App\Http\Controllers\Staff;

use App\Enums\GroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DepartmentsController extends Controller
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

        return Inertia::render('Staff/Departments/DepartmentsIndex', [
            'groups' => $departmentsSortedByMembershipAndUserCount,
            'myGroups' => $myDepartments,
        ]);
    }

    public function show(Group $department, Request $request)
    {
        return Inertia::render('Staff/Departments/ShowDepartment', [
            'group' => $department->loadCount('users')->only(['hashid', 'name', 'users_count']),
            'users' => $department->users()->withPivot('level')->get(['id', 'name', 'profile_photo_path'])->map(fn($user
            ) => [
                'id' => $user->hashid,
                'name' => $user->name,
                'profile_photo_path' => (is_null($user->profile_photo_path)) ? null : Storage::drive('s3-avatars')->url($user->profile_photo_path),
                'level' => $user->pivot->level,
                'title' => $user->pivot->title,
            ]),
            'canEdit' => $department->isAdmin($request->user())
        ]);
    }

    public function edit(Group $group)
    {

    }

    public function update(Request $request, Group $group)
    {
    }
}
