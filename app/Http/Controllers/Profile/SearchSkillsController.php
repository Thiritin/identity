<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSkillsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim();

        $skills = Skill::query()
            ->when($query->isNotEmpty(), fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($skills);
    }
}
