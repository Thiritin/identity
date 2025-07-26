<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\GroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\GroupStoreRequest;
use App\Http\Requests\GroupUpdateRequest;
use App\Http\Resources\V1\GroupCollection;
use App\Http\Resources\V1\GroupResource;
use App\Domains\Staff\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Group::class);

        $groups = Group::query();

        if ($request->user()->isStaff()) {
            $groups = $groups->where('type', GroupTypeEnum::Department)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $request->user()->id));
        } else {
            $groups = $groups->whereHas('users', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        $groups = $groups->simplePaginate(25);

        return new GroupCollection($groups);
    }

    public function store(GroupStoreRequest $request)
    {
        $this->authorize('create', Group::class);
        $group = Group::create($request->validationData());

        return new GroupResource($group);
    }

    public function show(Group $group, Request $request)
    {
        $this->authorize('view', [$group, $request->user()]);

        return new GroupResource($group);
    }

    public function update(GroupUpdateRequest $request, Group $group)
    {
        $this->authorize('update', [$group, $request->user()]);
        $group->fill($request->validationData());
        $group->save();

        return new GroupResource($group);
    }

    public function destroy(Group $group, Request $request)
    {
        $this->authorize('delete', [$group, $request->user()]);
        $group->delete();

        return response(null, 204);
    }
}
