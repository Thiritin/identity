<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Directory\StoreDepartmentRequest;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;

class DirectoryDepartmentController extends Controller
{
    public function store(StoreDepartmentRequest $request, Group $group): RedirectResponse
    {
        Group::create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'type' => GroupTypeEnum::Department,
            'parent_id' => $group->id,
        ]);

        return back();
    }
}
