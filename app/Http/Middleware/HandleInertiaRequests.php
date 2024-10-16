<?php

namespace App\Http\Middleware;

use App\Models\User;
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
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
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
     * @param  Request  $request
     * @return array
     */
    public function share(Request $request)
    {
        $user = null;
        if ($request->user()) {
            $user = array_merge([
                "id" => $request->user()->hashId(),
                "avatar" => ($request->user()->profile_photo_path) ? Storage::disk('s3-avatars')->url($request->user()->profile_photo_path) : null,
                "isAdmin" => $request->user()->hasRole(['admin', 'superadmin']),
                "roles" => $request->user()->getRoleNames(),
                "language" => "en",
                'departments' => $request->user()->groups()
                    ->where('type', 'department')
                    ->limit(10)
                    ->orderBy('name')
                    ->get(['id', 'name'])->values(),
            ], $request->user()->only('name', 'email'));
        }

        $staffMembers = null;
        // If user has departments
        if($user && $user['departments']->count() > 0) {
            $staffMembers = User::whereHas('groups', fn($q) => $q->where('type', 'department'))
                ->orderBy('name')
                ->get(['id','name']);
        }


        return array_merge(parent::share($request), [
            'user' => Route::is(['auth.login.view']) ? null : $user,
            'hideUserInfo' => Route::is(['auth.login.view', 'verification.notice']),
            'staffMemberList' => $staffMembers,
            'flash' => [
                'message' => fn() => $request->session()->get('message')
            ],
        ]);
    }
}
