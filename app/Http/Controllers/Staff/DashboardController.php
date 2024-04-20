<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\App;
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
        
        return inertia('Staff/Dashboard', [
            'apps' => $apps,
        ]);
    }
}
