<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Directory\StoreTeamRequest;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;

class DirectoryTeamController extends Controller
{
    public function store(StoreTeamRequest $request, Group $group): RedirectResponse
    {
        Group::create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'type' => GroupTypeEnum::Team,
            'parent_id' => $group->id,
        ]);

        return back();
    }
}
