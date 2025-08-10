<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupUserStoreRequest;
use App\Http\Resources\V1\GroupUserCollection;
use App\Http\Resources\V1\GroupUserResource;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GroupUserController extends Controller
{
    public function index(Group $group, Request $request)
    {
        // For staff members, they might not be group members but can still view
        $userInGroup = $group->users()->find($request->user()->id);
        if ($userInGroup && $userInGroup->pivot) {
            $this->authorize('view', [$userInGroup->pivot]);
        } else {
            // If not a group member, check if they can view the group itself
            $this->authorize('view', $group);
        }

        $selectFields = ['users.id', 'users.name', 'users.profile_photo_path', 'group_user.level', 'group_user.title'];
        if ($request->user()->tokenCan('view_full_staff_details')) {
            $selectFields[] = 'users.email';
        }

        return new GroupUserCollection(QueryBuilder::for($group->users())
            ->select($selectFields)
            ->allowedFilters(AllowedFilter::exact('level', 'group_user.level'))
            ->simplePaginate(100));
    }

    public function store(GroupUserStoreRequest $request, Group $group)
    {
        // For staff members, they might not be group members but can still create
        $userInGroup = $group->users()->find($request->user()->id);
        if ($userInGroup && $userInGroup->pivot) {
            $this->authorize('create', [$userInGroup->pivot]);
        } else {
            // If not a group member, check if they can update the group itself
            $this->authorize('update', $group);
        }

        $useField = isset($request->validationData()['email']) ? 'email' : 'id';

        $user = match ($useField) {
            'email' => User::where('email', $request->validationData()['email'])->firstOrFail(),
            'id' => User::findByHashidOrFail($request->validationData()['id']),
        };

        // validated email
        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['email' => 'User has not verified their email']);
        }

        // ensure user does not already exist, if he does, throw validation error
        if ($group->users->contains($user)) {
            throw ValidationException::withMessages([$useField => 'User is already in the group']);
        }

        $group->users()->attach($user, ['level' => $request->validationData()['level']]);

        return new GroupUserResource($group->users()->find($user->id));
    }

    public function destroy(Group $group, User $user, Request $request)
    {
        // For staff members, they might not be group members but can still delete
        $userInGroup = $group->users()->find($request->user()->id);
        if ($userInGroup && $userInGroup->pivot) {
            $this->authorize('delete', [$userInGroup->pivot]);
        } else {
            // If not a group member, check if they can update the group itself
            $this->authorize('update', $group);
        }

        $group->users()->detach($user);

        return response(null, 204);
    }
}
