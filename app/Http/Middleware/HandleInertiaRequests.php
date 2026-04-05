<?php

namespace App\Http\Middleware;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Convention;
use App\Models\User;
use App\Services\DirectoryTreeBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Storage;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     *
     * @param  Request  $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @param  Request  $request
     * @return array
     */
    public function share(Request $request)
    {
        $user = null;
        if ($request->user()) {
            $user = array_merge([
                'id' => $request->user()->hashid,
                'avatar' => ($request->user()->profile_photo_path) ? Storage::disk('s3-avatars')->url($request->user()->profile_photo_path) : null,
                'isAdmin' => $request->user()->is_admin,
                'isStaff' => $request->user()->isStaff(),
                'isTeamLead' => $request->user()->isTeamLead(),
                'isDirector' => $request->user()->isDirector(),
                'isDivisionDirector' => $request->user()->isDivisionDirector(),
                'isDeveloper' => $request->user()->is_developer,
                'language' => app()->getLocale(),
                'preferences' => $request->user()->preferences ?? [],
                'departments' => $request->user()->groups()
                    ->where('type', 'department')
                    ->limit(10)
                    ->orderBy('name')
                    ->get(['groups.id', 'groups.hashid', 'groups.slug', 'groups.name'])
                    ->map(fn ($g) => [
                        'id' => $g->id,
                        'hashid' => $g->hashid,
                        'slug' => $g->slug,
                        'name' => $g->name,
                        'title' => $g->pivot->title,
                        'level' => $g->pivot->level instanceof GroupUserLevel
                            ? $g->pivot->level->value
                            : $g->pivot->level,
                    ]),
                'memberSince' => $request->user()->created_at?->translatedFormat('F Y'),
            ], $request->user()->only('name', 'email'));
        }

        $staffMembers = null;
        // If user has departments
        if ($user && $user['departments']->count() > 0) {
            $staffMembers = User::whereHas('groups', fn ($q) => $q->where('type', 'department'))
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $directoryProps = [];
        if (Route::is('directory.*') && $request->user()) {
            $myGroupIds = $request->user()->groups()
                ->whereIn('type', [
                    GroupTypeEnum::Division,
                    GroupTypeEnum::Department,
                    GroupTypeEnum::Team,
                ])
                ->pluck('groups.id')->all();
            $directoryProps = [
                'directoryTree' => (new DirectoryTreeBuilder())->build($myGroupIds),
                'myGroupCount' => count($myGroupIds),
                'directorySelectedSlug' => null,
            ];
        }

        return array_merge(parent::share($request), [
            'locale' => app()->getLocale(),
            'version' => config('app.version'),
            'user' => Route::is(['auth.login.view']) ? null : $user,
            'hideUserInfo' => Route::is(['auth.login.view', 'verification.notice', 'auth.consent']),
            'staffMemberList' => $staffMembers,
            'backgroundImageUrl' => fn () => Convention::current()->first()?->background_image_url,
        ], $directoryProps);
    }
}
