<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\StaffResource;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    private function requireScope(string $scope): void
    {
        if (! in_array($scope, Auth::guard('api')->getScopes(), true)) {
            abort(403, 'Missing required scope: ' . $scope);
        }
    }

    public function me(Request $request)
    {
        $this->requireScope('staff.my.read');

        $user = $request->user();
        $user->loadMissing('groups');

        return StaffResource::make($user)->forViewer($user);
    }

    public function index(Request $request)
    {
        $this->requireScope('staff.all.read');

        $staffGroup = Group::where('system_name', 'staff')->firstOrFail();

        $query = $staffGroup->users()->with('groups');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $staff = $query->simplePaginate(25);

        return StaffResource::collection($staff)->forViewer($request->user());
    }

    public function show(Request $request, string $userHashid)
    {
        $this->requireScope('staff.all.read');

        $user = User::findByHashidOrFail($userHashid);

        if (! $user->isStaff()) {
            abort(404);
        }

        $user->loadMissing('groups');

        return StaffResource::make($user)->forViewer($request->user());
    }
}
