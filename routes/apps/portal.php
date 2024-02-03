<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Profile\Settings\TwoFactor\TotpSetupController;
use App\Http\Controllers\Profile\Settings\TwoFactor\YubikeySetupController;
use App\Http\Controllers\Profile\Settings\UpdatePasswordController;
use App\Http\Controllers\Profile\StoreAvatarController;
use App\Http\Controllers\Profile\UpdateProfileController;
use Illuminate\Support\Facades\Route;

// General Routes
Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::inertia('/settings/profile', 'Settings/Profile')->name('settings.profile');
Route::post('/settings/profile/update', UpdateProfileController::class)->name('settings.update-profile.update');

Route::inertia('/settings/update-password', 'Settings/UpdatePassword')->name('settings.update-password');
Route::post('/settings/update-password', UpdatePasswordController::class)->name('settings.update-password.store');

Route::get('/settings/two-factor',
    \App\Http\Controllers\Profile\Settings\TwoFactor\TwoFactorController::class)->name('settings.two-factor');
/** Two Factor */
Route::get('/settings/two-factor/totp', [TotpSetupController::class, 'show'])->name('settings.two-factor.totp');
Route::post('/settings/two-factor/totp/store',
    [TotpSetupController::class, 'store'])->name('settings.two-factor.totp.store');
Route::delete('/settings/two-factor/totp/destroy',
    [TotpSetupController::class, 'destroy'])->name('settings.two-factor.totp.destroy');
/** Yubico */
Route::get('/settings/two-factor/yubikey', [YubikeySetupController::class, 'index'])
    ->name('settings.two-factor.yubikey');
Route::post('/settings/two-factor/yubikey/setup', [YubikeySetupController::class, 'store'])
    ->name('settings.two-factor.yubikey.store');
Route::delete('/settings/two-factor/yubikey/destroy', [YubikeySetupController::class, 'destroy'])
    ->name('settings.two-factor.yubikey.destroy');

Route::post('/profile/avatar/store', StoreAvatarController::class)
    ->name('profile.avatar.store');

