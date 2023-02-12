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
        $apps = App::where(function (Builder $q) {

            $q->where('public', '=', true)->orWhereHas('groups', function (Builder $q) {
                $q->whereIn('id', Auth::user()->groups->pluck('id'));
            });
        })->where(function (Builder $q) {
            $q->where(function (Builder $q) {
                $q->whereDate('starts_at', '<=', now())->orWhereNull('starts_at');
            })->where(function (Builder $q) {
                $q->whereDate('ends_at', '>=', now())->orWhereNull('ends_at');
            });
        })->get();

        return Inertia::render('Dashboard', [
            'apps' => $apps,
        ]);
    }
}
