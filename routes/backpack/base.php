<?php

/*
|--------------------------------------------------------------------------
| Backpack\Base Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are
| handled by the Backpack\Base package.
|
*/

Route::group(
    [
        'namespace' => 'Backpack\CRUD\app\Http\Controllers',
        'prefix' => config('backpack.base.route_prefix', ''),
        'middleware' => array_merge(
            (array)config('backpack.base.web_middleware', 'web'),
            (array)config('backpack.base.middleware_key', 'admin')
        ),
    ],
    function () {
        Route::get('dashboard', 'AdminController@dashboard')->name('backpack.dashboard');
        Route::get('/', 'AdminController@redirect')->name('backpack');

        // OIDC Frontchannel Logout
        Route::get('frontchannel-logout', function () {
            Auth::guard('admin')->logout();
        })->middleware(['auth'])->name('frontchannel_logout');

        Route::get('/logout', function () {
            return redirect('/oauth2/sessions/logout');
        })->name('logout');

        if (config('backpack.base.setup_my_account_routes')) {
            Route::get('edit-account-info', 'MyAccountController@getAccountInfoForm')->name('backpack.account.info');
            Route::post('edit-account-info', 'MyAccountController@postAccountInfoForm')->name('backpack.account.info.store');
            Route::post('change-password', 'MyAccountController@postChangePasswordForm')->name('backpack.account.password');
        }
    });
