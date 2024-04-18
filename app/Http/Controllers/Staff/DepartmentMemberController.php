<?php

namespace App\Http\Controllers\Staff;

use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DepartmentMemberController extends Controller
{
    public function create(Group $department)
    {
        return Inertia::render('Staff/DepartmentMember/DepartmentMemberCreate', [
            'department' => $department,
        ]);
    }

    public function store(Group $department, Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        // look up by email
        $user = User::where('email', $request->email)->first();
        // If email not verified throw validationexception
        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['email' => 'User has not verified their email']);
        }
        // If user is already in the department throw validationexception
        if ($department->users->contains($user)) {
            throw ValidationException::withMessages(['email' => 'User is already in the department']);
        }
        // Attach user to department
        $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
        return redirect()->route('staff.departments.show', $department);
    }

    public function edit(Group $department, User $user)
    {
        return Inertia::render('Staff/DepartmentMember/DepartmentMemberEdit', [
            'department' => $department,
            'user' => $user,
        ]);
    }
}
