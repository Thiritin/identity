<?php

namespace App\Http\Controllers\Api\v2\Concerns;

use App\Enums\GroupTypeEnum;
use App\Models\User;
use App\Support\ScopeChecker;
use Illuminate\Http\Request;

trait ChecksScopes
{
    private function requireScope(string $scope): void
    {
        ScopeChecker::require($scope);
    }

    private function hasScope(string $scope): bool
    {
        return ScopeChecker::has($scope);
    }

    private function resolveUser(Request $request, string $user): User
    {
        if ($user === 'me') {
            return $request->user();
        }

        return User::findByHashidOrFail($user);
    }

    private function authorizeManageUser(User $viewer, User $target): void
    {
        $targetGroupIds = $target->groups()
            ->whereIn('groups.type', [
                GroupTypeEnum::Department->value,
                GroupTypeEnum::Division->value,
                GroupTypeEnum::Team->value,
            ])
            ->pluck('groups.id');

        if ($targetGroupIds->isEmpty()) {
            abort(403, 'You do not have permission to manage this user.');
        }

        $canManage = $viewer->groups()
            ->whereIn('groups.id', $targetGroupIds)
            ->get()
            ->contains(fn ($group) => $group->pivot->canManageMembers());

        if (! $canManage) {
            abort(403, 'You do not have permission to manage this user.');
        }
    }
}
