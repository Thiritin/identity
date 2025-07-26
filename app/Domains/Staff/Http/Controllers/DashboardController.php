<?php

namespace App\Domains\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\User\Models\App;
use App\Domains\Staff\Models\Group;
use App\Domains\Staff\Enums\GroupTypeEnum;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $apps = App::where(function (Builder $q) {
            $q->where('public', '=', true)->orWhereHas('groups', function (Builder $q) {
                $q->whereIn('id', Auth::guard('staff')->user()->groups->pluck('id'));
            });
        })->where(function (Builder $q) {
            $q->where(function (Builder $q) {
                $q->whereDate('starts_at', '<=', now())->orWhereNull('starts_at');
            })->where(function (Builder $q) {
                $q->whereDate('ends_at', '>=', now())->orWhereNull('ends_at');
            });
        })->orderBy('priority')->get(['id', 'name', 'description', 'icon', 'url']);

        // Get organization stats
        $orgStats = [
            'total_divisions' => Group::where('type', GroupTypeEnum::Division)->count(),
            'total_departments' => Group::where('type', GroupTypeEnum::Department)->count(),
            'total_teams' => Group::where('type', GroupTypeEnum::Team)->count(),
            'user_rank' => Auth::guard('staff')->user()->getRank()->getDisplayName(),
            'user_groups' => Auth::guard('staff')->user()->groups->map(function ($group) {
                return [
                    'name' => $group->name,
                    'type' => $group->type->getDisplayName(),
                    'level' => $group->pivot->level->getDisplayName(),
                ];
            }),
        ];

        return inertia('Staff/Dashboard', [
            'apps' => $apps,
            'orgStats' => $orgStats,
        ]);
    }
}
