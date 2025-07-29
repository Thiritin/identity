<?php

namespace App\Domains\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\User\Models\User;
use App\Domains\Staff\Models\Group;
use App\Domains\Staff\Enums\GroupTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
        ]);

        $query = $request->get('query');
        $results = [];

        // Search Users
        $users = User::whereHas('groups', function ($q) {
                $q->where('type', GroupTypeEnum::Department);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('username', 'like', "%{$query}%")
                  ->orWhere('nickname', 'like', "%{$query}%");
            })
            ->with(['groups'])
            ->limit(10)
            ->get();

        foreach ($users as $user) {
            $results[] = [
                'id' => $user->id,
                'type' => 'user',
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'profile_photo_url' => $user->profile_photo_url,
                'rank' => $user->getRank()->getDisplayName(),
                'departments' => $user->groups->where('type', GroupTypeEnum::Department)->pluck('name')->toArray(),
            ];
        }

        // Search Groups (Teams)
        $groups = Group::where('type', GroupTypeEnum::Team)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->withCount('users')
            ->limit(10)
            ->get();

        foreach ($groups as $group) {
            $results[] = [
                'id' => $group->id,
                'hashid' => $group->hashid,
                'type' => 'group',
                'name' => $group->name,
                'description' => $group->description,
                'logo_url' => $group->logo_url,
                'members_count' => $group->users_count,
            ];
        }

        // Search Departments
        $departments = Group::where('type', GroupTypeEnum::Department)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->withCount('users')
            ->limit(10)
            ->get();

        foreach ($departments as $department) {
            $results[] = [
                'id' => $department->id,
                'hashid' => $department->hashid,
                'type' => 'department',
                'name' => $department->name,
                'description' => $department->description,
                'logo_url' => $department->logo_url,
                'members_count' => $department->users_count,
            ];
        }

        return response()->json([
            'results' => $results,
            'query' => $query,
            'total' => count($results),
        ]);
    }
}