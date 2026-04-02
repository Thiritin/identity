<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppCategory;
use Auth;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user()->loadMissing('groups');

        $apps = App::with('category')
            ->where(function (Builder $q) use ($user) {
                $q->where('public', '=', true)->orWhereHas('groups', function (Builder $q) use ($user) {
                    $q->whereIn('id', $user->groups->pluck('id'));
                });
            })->where(function (Builder $q) {
                $q->where(function (Builder $q) {
                    $q->whereDate('starts_at', '<=', now())->orWhereNull('starts_at');
                })->where(function (Builder $q) {
                    $q->whereDate('ends_at', '>=', now())->orWhereNull('ends_at');
                });
            })->orderBy('priority')->get();

        $mapApp = fn (App $app) => [
            'id' => $app->id,
            'name' => $app->name,
            'description' => $app->description,
            'image_url' => $app->image ? Storage::url($app->image) : null,
            'url' => $app->url,
        ];

        // Featured registration app — shown as hero when active and public
        $registration = null;
        $registrationClientId = config('services.registration.client_id');
        if ($registrationClientId) {
            $registrationApp = $apps->first(fn (App $app) => $app->client_id === $registrationClientId);
            if ($registrationApp) {
                $registration = $mapApp($registrationApp);
                $apps = $apps->reject(fn (App $app) => $app->client_id === $registrationClientId)->values();
            }
        }

        $pinned = $apps->where('pinned', true)->values();
        $nonPinned = $apps->reject(fn (App $app) => $app->pinned)->values();

        // Build categories with their non-pinned apps
        $categoryIds = $nonPinned->pluck('category_id')->filter()->unique()->values();
        $categories = AppCategory::whereIn('id', $categoryIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (AppCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'apps' => $nonPinned->where('category_id', $category->id)->values()->map($mapApp),
            ])->values();

        return Inertia::render('Dashboard', [
            'registration' => $registration,
            'pinned' => $pinned->map($mapApp),
            'categories' => $categories,
            'uncategorized' => $nonPinned->whereNull('category_id')->values()->map($mapApp),
        ]);
    }
}
