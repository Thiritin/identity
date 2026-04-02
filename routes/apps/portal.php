<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Profile\Settings\TwoFactor\TotpSetupController;
use App\Http\Controllers\Profile\Settings\TwoFactor\YubikeySetupController;
use App\Http\Controllers\Profile\Settings\UpdatePasswordController;
use App\Http\Controllers\Profile\SecurityController;
use App\Http\Controllers\Profile\StoreAvatarController;
use App\Http\Controllers\Profile\UpdatePreferencesController;
use App\Http\Controllers\Profile\UpdateProfileController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

// General Routes
Route::get('/dashboard', DashboardController::class)->name('dashboard');
// Forward / to /dashboard
Route::redirect('/', '/dashboard');
Route::inertia('/settings/profile', 'Settings/Profile')->name('settings.profile');
Route::post('/settings/profile/update', UpdateProfileController::class)
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.update-profile.update');

Route::redirect('/settings/update-password', '/settings/security', 301);
Route::post('/settings/update-password', UpdatePasswordController::class)
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.update-password.store');

Route::get('/settings/security', SecurityController::class)->name('settings.security');

Route::redirect('/settings/two-factor', '/settings/security', 301);
/** Two Factor */
Route::redirect('/settings/two-factor/totp', '/settings/security', 301);
Route::post('/settings/two-factor/totp/store',
    [TotpSetupController::class, 'store'])
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.two-factor.totp.store');
Route::get('/settings/two-factor/totp/setup', [TotpSetupController::class, 'setup'])
    ->name('settings.two-factor.totp.setup');
Route::delete('/settings/two-factor/totp/destroy',
    [TotpSetupController::class, 'destroy'])
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.two-factor.totp.destroy');
/** Yubico */
Route::redirect('/settings/two-factor/yubikey', '/settings/security', 301);
Route::post('/settings/two-factor/yubikey/setup', [YubikeySetupController::class, 'store'])
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.two-factor.yubikey.store');
Route::delete('/settings/two-factor/yubikey/destroy', [YubikeySetupController::class, 'destroy'])
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.two-factor.yubikey.destroy');

Route::post('/profile/avatar/store', StoreAvatarController::class)
    ->name('profile.avatar.store');

Route::post('/settings/preferences', UpdatePreferencesController::class)
    ->name('settings.preferences.update');
