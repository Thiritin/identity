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
        return Inertia::render('Staff/Departments/DepartmentsIndex', [
            'groups' => Group::where('type', GroupTypeEnum::Department)
                ->withCount('users')->get(),
            'myGroups' => $request->user()->groups()->where('type', GroupTypeEnum::Department)
                ->withCount('users')->get(),
        ]);
    }

    public function show(Group $department, Request $request)
    {
        return Inertia::render('Staff/Departments/ShowDepartment', [
            'group' => $department->loadCount('users'),
            'users' => $department->users()->withPivot('level')->get(['id', 'name', 'profile_photo_path'])->map(fn($user
            ) => [
                'id' => $user->id,
                'name' => $user->name,
                'profile_photo_path' => Storage::drive('s3-avatars')->url($user->profile_photo_path),
                'level' => $user->pivot->level,
                'title' => $user->pivot->title,
            ])
        ]);
    }

    public function edit(Group $group)
    {
    }

    public function update(Request $request, Group $group)
    {
    }
}
