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
        $this->authorize('view', [$group->users()->find($request->user()->id)->pivot]);

        return new GroupUserCollection(QueryBuilder::for($group->users())
            ->allowedFilters(AllowedFilter::exact('level', 'group_user.level'))
            ->simplePaginate(100));
    }

    public function store(GroupUserStoreRequest $request, Group $group)
    {
        $this->authorize('create', [$group->users()->find($request->user()->id)->pivot]);

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
        $pivot = $group->users()->find($request->user()->id)->pivot;
        $this->authorize('delete', [$pivot]);
        $group->users()->detach($user);

        return response(null, 204);
    }
}
