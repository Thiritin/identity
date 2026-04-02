<?php

namespace App\Filament\Widgets;

use App\Models\App;
use App\Models\Group;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Activitylog\Models\Activity;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count()),
            Stat::make('Verified Users', User::whereNotNull('email_verified_at')->count())
                ->color('success'),
            Stat::make('Total Groups', Group::count()),
            Stat::make('Total Apps', App::count()),
            Stat::make('Failed Logins (24h)', Activity::where('description', 'login-failed')
                ->where('created_at', '>=', now()->subDay())
                ->count())
                ->color('danger'),
        ];
    }
}
