<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $twoFactorCount = User::whereHas('twoFactors')->count();
        $suspendedCount = User::whereNotNull('suspended_at')->count();

        $twoFactorPercentage = $totalUsers > 0
            ? round(($twoFactorCount / $totalUsers) * 100, 1)
            : 0;

        return [
            Stat::make('Total Users', $totalUsers),
            Stat::make('2FA Enabled', $twoFactorCount)
                ->description("{$twoFactorPercentage}% of all users"),
            Stat::make('Suspended', $suspendedCount)
                ->color($suspendedCount > 0 ? 'danger' : 'success'),
        ];
    }
}
