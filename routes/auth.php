<?php

use App\Http\Controllers\AuthController;

Route::get('/sso/login', [AuthController::class, 'login'])->name('login.redirect');
Route::get('/sso/callback', [AuthController::class, 'loginCallback'])->name('login.callback');
Route::get('/sso/logout', [AuthController::class, 'logout'])->name('login.logout');
Route::get('/sso/frontchannel-logout', [AuthController::class, 'frontchannelLogout'])
    ->name('login.frontchannel-logout');
