<?php

namespace App\Http\Controllers;

use App\Models\App;
use Auth;
use Illuminate\Contracts\Database\Query\Builder;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user()->loadMissing('groups');
        $apps = App::where(function (Builder $q) use ($user) {
            $q->where('public', '=', true)->orWhereHas('groups', function (Builder $q) use ($user) {
                $q->whereIn('id', $user->groups->pluck('id'));
            });
        })->where(function (Builder $q) {
            $q->where(function (Builder $q) {
                $q->whereDate('starts_at', '<=', now())->orWhereNull('starts_at');
            })->where(function (Builder $q) {
                $q->whereDate('ends_at', '>=', now())->orWhereNull('ends_at');
            });
        })->orderBy('priority')->get(['id', 'name', 'description', 'icon', 'url']);

        return Inertia::render('Dashboard', [
            'apps' => $apps,
        ]);
    }
}
