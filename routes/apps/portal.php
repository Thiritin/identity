<?php

use App\Domains\User\Http\Controllers\DashboardController;
use App\Domains\User\Http\Controllers\Settings\Security\PasskeyController;
use App\Domains\User\Http\Controllers\Settings\TwoFactor\TotpSetupController;
use App\Domains\User\Http\Controllers\Settings\TwoFactor\TwoFactorController;
use App\Domains\User\Http\Controllers\Settings\TwoFactor\YubikeySetupController;
use App\Domains\User\Http\Controllers\Settings\UpdatePasswordController;
use App\Domains\User\Http\Controllers\StoreAvatarController;
use App\Domains\User\Http\Controllers\UpdateProfileController;
use Illuminate\Support\Facades\Route;

// General Routes
Route::get('/dashboard', DashboardController::class)->name('dashboard');
// Forward / to /dashboard
Route::redirect('/', '/dashboard');
Route::inertia('/settings/profile', 'Settings/Profile')->name('settings.profile');
Route::post('/settings/profile/update', UpdateProfileController::class)
    ->middleware([\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class])
    ->name('settings.update-profile.update');

Route::inertia('/settings/update-password', 'Settings/UpdatePassword')->name('settings.update-password');
Route::post('/settings/update-password', UpdatePasswordController::class)
    ->middleware([\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class])
    ->name('settings.update-password.store');

Route::get('/settings/two-factor',
    TwoFactorController::class)->name('settings.two-factor');
/** Two Factor */
Route::get('/settings/two-factor/totp', [TotpSetupController::class, 'show'])->name('settings.two-factor.totp');
Route::post('/settings/two-factor/totp/store',
    [TotpSetupController::class, 'store'])
    ->middleware([\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class])
    ->name('settings.two-factor.totp.store');
Route::delete('/settings/two-factor/totp/destroy',
    [TotpSetupController::class, 'destroy'])
    ->middleware([\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class])
    ->name('settings.two-factor.totp.destroy');
/** Yubico */
Route::get('/settings/two-factor/yubikey', [YubikeySetupController::class, 'index'])
    ->name('settings.two-factor.yubikey');
Route::post('/settings/two-factor/yubikey/setup', [YubikeySetupController::class, 'store'])
    ->middleware([\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class])
    ->name('settings.two-factor.yubikey.store');
Route::delete('/settings/two-factor/yubikey/destroy', [YubikeySetupController::class, 'destroy'])
    ->middleware([\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class])
    ->name('settings.two-factor.yubikey.destroy');

/** Security */
Route::get('/settings/security/activity', function () {
    $user = Auth::user();
    $securityService = app(\App\Domains\Auth\Services\SecurityNotificationService::class);
    $events = $securityService->getSecurityEvents($user);
    
    return Inertia::render('User/Security/Activity', [
        'events' => $events
    ]);
})->name('settings.security.activity');

/** Passkeys */
Route::get('/settings/security/passkeys', [PasskeyController::class, 'index'])
    ->name('settings.security.passkeys');
Route::post('/settings/security/passkeys/register', [PasskeyController::class, 'register'])
    ->name('settings.security.passkeys.register');
Route::post('/settings/security/passkeys', [PasskeyController::class, 'store'])
    ->middleware([\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class])
    ->name('settings.security.passkeys.store');
Route::patch('/settings/security/passkeys/{credential}', [PasskeyController::class, 'update'])
    ->middleware([\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class])
    ->name('settings.security.passkeys.update');
Route::delete('/settings/security/passkeys/{credential}', [PasskeyController::class, 'destroy'])
    ->name('settings.security.passkeys.destroy');

Route::post('/profile/avatar/store', StoreAvatarController::class)
    ->name('profile.avatar.store');
