<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        return "Happy face";
    }

    public function store(Request $request)
    {
    }

    public function show(Group $group)
    {
    }

    public function update(Request $request, Group $group)
    {
    }

    public function destroy(Group $group)
    {
    }
}
