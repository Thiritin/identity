<?php

namespace App\Http\Controllers\Api\v2;

use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\StoreGroupMemberRequest;
use App\Http\Requests\Api\v2\UpdateGroupMemberRequest;
use App\Http\Resources\V2\GroupMemberResource;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class GroupMemberController extends Controller
{
    private function requireScope(string $scope): void
    {
        if (! in_array($scope, Auth::guard('api')->getScopes(), true)) {
            abort(403, 'Missing required scope: ' . $scope);
        }
    }

    private function resolveGroup(string $hashid): Group
    {
        return Group::findByHashidOrFail($hashid);
    }

    private function authorizeGroupManagement(Group $group, Request $request): void
    {
        $userInGroup = $group->users()->find($request->user()->id);
        if ($userInGroup && $userInGroup->pivot) {
            $this->authorize('create', [$userInGroup->pivot]);
        } else {
            $this->authorize('update', $group);
        }
    }

    public function index(Request $request, string $groupHashid)
    {
        $this->requireScope('groups.read');

        $group = $this->resolveGroup($groupHashid);
        $this->authorize('view', [$group, $request->user()]);

        $query = $group->users();

        if ($request->filled('level')) {
            $levels = collect(explode(',', $request->input('level')))
                ->map(fn (string $level) => GroupUserLevel::tryFrom(trim($level)))
                ->filter()
                ->all();

            if (! empty($levels)) {
                $query->wherePivotIn('level', array_map(fn ($l) => $l->value, $levels));
            }
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        return GroupMemberResource::collection(
            $query->simplePaginate($request->integer('per_page', 25))
        );
    }

    public function store(StoreGroupMemberRequest $request, string $groupHashid)
    {
        $this->requireScope('groups.write');

        $group = $this->resolveGroup($groupHashid);
        $this->authorizeGroupManagement($group, $request);

        $user = $request->filled('email')
            ? User::where('email', $request->validated('email'))->firstOrFail()
            : User::findByHashidOrFail($request->validated('user_id'));

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['email' => 'User has not verified their email.']);
        }

        if ($group->users->contains($user)) {
            throw ValidationException::withMessages(['user_id' => 'User is already a member of this group.']);
        }

        $level = GroupUserLevel::tryFrom($request->validated('level', 'member')) ?? GroupUserLevel::Member;

        $group->users()->attach($user, [
            'level' => $level,
            'title' => $request->validated('title'),
        ]);

        return (new GroupMemberResource($group->users()->find($user->id)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateGroupMemberRequest $request, string $groupHashid, string $userHashid)
    {
        $this->requireScope('groups.write');

        $group = $this->resolveGroup($groupHashid);
        $this->authorizeGroupManagement($group, $request);

        $user = User::findByHashidOrFail($userHashid);

        if (! $group->users()->where('user_id', $user->id)->exists()) {
            abort(404);
        }

        $pivotData = [];

        if ($request->has('level')) {
            $level = GroupUserLevel::tryFrom($request->validated('level'));
            if ($level) {
                $pivotData['level'] = $level;
            }
        }

        if ($request->has('title')) {
            $pivotData['title'] = $request->validated('title');
        }

        if (! empty($pivotData)) {
            $group->users()->updateExistingPivot($user->id, $pivotData);
        }

        return new GroupMemberResource($group->users()->find($user->id));
    }

    public function destroy(Request $request, string $groupHashid, string $userHashid)
    {
        $this->requireScope('groups.write');

        $group = $this->resolveGroup($groupHashid);
        $this->authorizeGroupManagement($group, $request);

        $user = User::findByHashidOrFail($userHashid);
        $group->users()->detach($user);

        return response()->noContent();
    }
}
