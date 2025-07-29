<?php

namespace App\Domains\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\User\Models\User;
use App\Domains\Staff\Models\Group;
use App\Domains\Staff\Enums\GroupTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['groups.pivot', 'webauthnCredentials'])
            ->whereHas('groups', function ($query) {
                $query->where('type', GroupTypeEnum::Department);
            });

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhereHas('groups', function ($groupQuery) use ($search) {
                      $groupQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply department filter
        if ($request->filled('department')) {
            $query->whereHas('groups', function ($groupQuery) use ($request) {
                $groupQuery->where('system_name', $request->get('department'));
            });
        }

        // Apply role filter
        if ($request->filled('role')) {
            $role = $request->get('role');
            $query->whereHas('groups', function ($groupQuery) use ($role) {
                $groupQuery->wherePivot('level', $role);
            });
        }

        $users = $query->paginate(20)->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'profile_photo_url' => $user->profile_photo_url,
                'rank' => $user->getRank()->getDisplayName(),
                'city' => $user->city,
                'country' => $user->country,
                'telegram_username' => $user->telegram_username,
                'phone_numbers' => $user->phone_numbers,
                'languages' => $user->languages,
                'joined_ef_year' => $user->joined_ef_year,
                'credit_as' => $user->credit_as,
                'first_ef_year' => $user->first_ef_year,
                'departments' => $user->groups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'type' => $group->type->getDisplayName(),
                        'pivot' => [
                            'level' => $group->pivot->level->getDisplayName(),
                            'title' => $group->pivot->title,
                        ],
                    ];
                }),
            ];
        });

        return inertia('Staff/Users/UsersIndex', [
            'users' => $users,
            'filters' => $request->only(['search', 'department', 'role']),
        ]);
    }

    public function show(User $user)
    {
        $user->load(['groups.pivot', 'webauthnCredentials']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'profile_photo_url' => $user->profile_photo_url,
            'rank' => $user->getRank()->getDisplayName(),
            'city' => $user->city,
            'country' => $user->country,
            'telegram_username' => $user->telegram_username,
            'phone_numbers' => $user->phone_numbers,
            'languages' => $user->languages,
            'joined_ef_year' => $user->joined_ef_year,
            'credit_as' => $user->credit_as,
            'first_ef_year' => $user->first_ef_year,
            'date_of_birth' => $user->date_of_birth,
            'departments' => $user->groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'type' => $group->type->getDisplayName(),
                    'pivot' => [
                        'level' => $group->pivot->level->getDisplayName(),
                        'title' => $group->pivot->title,
                    ],
                ];
            }),
        ]);
    }
}