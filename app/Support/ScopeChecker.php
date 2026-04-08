<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class ScopeChecker
{
    public static function has(string $scope): bool
    {
        $scopes = Auth::guard('api')->getScopes();

        if (in_array($scope, $scopes, true)) {
            return true;
        }

        // ReadWrite implies Read
        $readWriteVariant = str_replace('.Read', '.ReadWrite', $scope);
        if ($readWriteVariant !== $scope && in_array($readWriteVariant, $scopes, true)) {
            return true;
        }

        // .All implies non-.All
        if (! str_ends_with($scope, '.All')) {
            $allVariant = $scope . '.All';
            if (in_array($allVariant, $scopes, true)) {
                return true;
            }

            $readWriteAllVariant = str_replace('.Read', '.ReadWrite', $scope) . '.All';
            if ($readWriteAllVariant !== $allVariant && in_array($readWriteAllVariant, $scopes, true)) {
                return true;
            }
        }

        return false;
    }

    public static function require(string $scope): void
    {
        if (! static::has($scope)) {
            abort(403, 'Missing required scope: ' . $scope);
        }
    }
}
