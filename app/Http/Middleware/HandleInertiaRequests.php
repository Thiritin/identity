<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
     * @param Request $request
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
     * @param Request $request
     * @return array
     */
    public function share(Request $request)
    {
        $user = null;
        if ($request->user()) {
            $user = array_merge([
                "id" => $request->user()->hashId(),
                "avatar" => ($request->user()->profile_photo_path) ? Storage::disk('avatars')->url($request->user()->profile_photo_path) : null,
                "isAdmin" => $request->user()->hasRole(['admin', 'superadmin']),
                "roles" => $request->user()->getRoleNames(),
                "language" => "en"
            ], $request->user()->only('name', 'email'));
        }
        return array_merge(parent::share($request), [
            'user' => $user,
            'flash' => [
                'message' => fn() => $request->session()->get('message')
            ],
        ]);
    }
}
