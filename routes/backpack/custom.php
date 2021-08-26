<?php

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\GroupCrudController;
use App\Http\Controllers\Admin\UserCrudController;

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array)config('backpack.base.web_middleware', 'web'),
        (array)config('backpack.base.middleware_key', 'admin')
    ),
], function () {
    Route::crud('user', UserCrudController::class);
    Route::crud('group', GroupCrudController::class);
    /**
     * Authentication
     */
    Route::get('callback', [LoginController::class, 'init'])
        ->name('admin.login.callback')
        ->middleware('guest')
        ->withoutMiddleware(config('backpack.base.middleware_key', 'admin'));
    Route::get('login', [LoginController::class, 'init'])
        ->name('admin.login.init')
        ->middleware('guest:admin')
        ->withoutMiddleware(config('backpack.base.middleware_key', 'admin'));
});
