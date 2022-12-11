<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupStoreRequest;
use App\Http\Requests\GroupUpdateRequest;
use App\Http\Resources\GroupCollection;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $this->authorize("viewAny", Group::class);
        return new GroupCollection(Group::simplePaginate(25));
    }

    public function store(GroupStoreRequest $request)
    {
        $this->authorize("create", Group::class);
        $group = Group::create($request->validationData());
        return new GroupResource($group);
    }

    public function show(Group $group, Request $request)
    {
        $this->authorize("view", [$group, $request->user()]);
        return new GroupResource($group);
    }

    public function update(GroupUpdateRequest $request, Group $group)
    {
        $this->authorize("update", [$group, $request->user()]);
        $group->fill($request->validationData());
        $group->save();
        return new GroupResource($group);
    }

    public function destroy(Group $group, Request $request)
    {
        $this->authorize("delete", [$group, $request->user()]);
        $group->delete();
        return response(null, 204);
    }
}
