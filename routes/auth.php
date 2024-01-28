<?php

use App\Http\Controllers\AuthController;

Route::get('/auth/{app}/login', [AuthController::class, 'login'])->name('login.apps.redirect');
Route::get('/auth/{app}/callback', [AuthController::class, 'loginCallback'])->name('login.apps.callback');
Route::get('/auth/{app}/logout', [AuthController::class, 'logout'])->name('login.apps.logout');
Route::get('/auth/{app}/frontchannel-logout', [AuthController::class, 'frontchannelLogout'])
    ->name('login.apps.frontchannel-logout');
