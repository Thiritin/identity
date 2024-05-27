<?php

namespace App\Http\Controllers\Staff;

use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
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

    public function edit(Group $department, User $member)
    {
        return Inertia::render('Staff/DepartmentMember/DepartmentMemberEdit', [
            'department' => $department,
            // Load pivot data
            'member' => $department->users()->where('user_id', $member->id)->select(['id', 'name'])->first()
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Group $department, User $member, Request $request)
    {
        if ($member->id == $request->user()->id) {
            throw ValidationException::withMessages(["You cannot update your own level."]);
        }

        $data = $request->validate([
            'level' => new Enum(GroupUserLevel::class),
        ]);

        $requestMember = $department->users()->find($request->user())->pivot;
        $this->authorize("update", $requestMember);

        $pivot = $department->users()->find($member->id)->pivot;
        $pivot->update($data);

        return to_route("staff.departments.show", ['department' => $department->hashid()]);
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function destroy(Group $department, User $member, Request $request)
    {
        if ($member->id === $request->user()->id) {
            throw ValidationException::withMessages(["You cannot remove yourself."]);
        }

        $requestMember = $department->users()->find($member)->pivot;
        $this->authorize('delete', $requestMember);
        $department->users()->detach($member);
    }
}
