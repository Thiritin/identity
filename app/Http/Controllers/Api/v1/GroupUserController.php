<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupUserStoreRequest;
use App\Http\Resources\V1\GroupUserCollection;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
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
        $user = User::whereEmail($request->validationData()['email'])->firstOrFail();
        $group->users()->attach($user, ['level' => $request->validationData()['level']]);
    }

    public function destroy(Group $group, User $user, Request $request)
    {
        $pivot = $group->users()->find($request->user()->id)->pivot;
        $this->authorize('delete', [$pivot]);
        $group->users()->detach($user);
    }
}
