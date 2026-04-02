<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Profile\SecurityController;
use App\Http\Controllers\Profile\Settings\ChangeEmailController;
use App\Http\Controllers\Profile\Settings\ConfirmPasswordController;
use App\Http\Controllers\Profile\Settings\SessionController;
use App\Http\Controllers\Profile\Settings\TwoFactor\BackupCodesController;
use App\Http\Controllers\Profile\Settings\TwoFactor\TotpSetupController;
use App\Http\Controllers\Profile\Settings\TwoFactor\YubikeySetupController;
use App\Http\Controllers\Profile\Settings\UpdatePasswordController;
use App\Http\Controllers\Profile\ShowProfileController;
use App\Http\Controllers\Profile\StoreAvatarController;
use App\Http\Controllers\Profile\UpdateGroupCreditAsController;
use App\Http\Controllers\Profile\UpdatePreferencesController;
use App\Http\Controllers\Profile\UpdateProfileController;
use App\Http\Controllers\Profile\UpdateStaffProfileController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

// General Routes
Route::get('/dashboard', DashboardController::class)->name('dashboard');
// Forward / to /dashboard
Route::redirect('/', '/dashboard');
Route::get('/settings/profile', ShowProfileController::class)->name('settings.profile');
Route::post('/settings/profile/update', UpdateProfileController::class)
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.update-profile.update');
Route::post('/settings/staff-profile/update', UpdateStaffProfileController::class)
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.staff-profile.update');
Route::post('/settings/staff-profile/credit-as', UpdateGroupCreditAsController::class)
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.staff-profile.credit-as');

Route::get('/settings/security', [SecurityController::class, 'index'])->name('settings.security');

Route::middleware('sudo')->group(function () {
    Route::get('/settings/security/password', [SecurityController::class, 'password'])->name('settings.security.password');
    Route::get('/settings/security/email', [SecurityController::class, 'email'])->name('settings.security.email');
    Route::get('/settings/security/totp', [SecurityController::class, 'totp'])->name('settings.security.totp');
    Route::get('/settings/security/yubikey', [SecurityController::class, 'yubikey'])->name('settings.security.yubikey');
    Route::get('/settings/security/backup-codes', [SecurityController::class, 'backupCodes'])->name('settings.security.backup-codes');

    Route::post('/settings/update-password', UpdatePasswordController::class)
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.update-password.store');

    Route::post('/settings/security/email', ChangeEmailController::class)
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.security.email.store');

    /** Two Factor */
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
    Route::post('/settings/two-factor/yubikey/setup', [YubikeySetupController::class, 'store'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.two-factor.yubikey.store');
    Route::delete('/settings/two-factor/yubikey/destroy', [YubikeySetupController::class, 'destroy'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.two-factor.yubikey.destroy');

    /** Backup Codes */
    Route::post('/settings/two-factor/backup-codes/regenerate', [BackupCodesController::class, 'regenerate'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.two-factor.backup-codes.regenerate');

    /** Sessions */
    Route::get('/settings/security/sessions', [SecurityController::class, 'sessions'])->name('settings.security.sessions');
    Route::delete('/settings/security/sessions/{session}', [SessionController::class, 'destroy'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.security.sessions.destroy');
    Route::delete('/settings/security/sessions', [SessionController::class, 'destroyOthers'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.security.sessions.destroy-others');
});

Route::post('/settings/security/confirm-password', ConfirmPasswordController::class)
    ->name('settings.security.confirm-password');

Route::redirect('/settings/two-factor', '/settings/security', 301);
Route::redirect('/settings/two-factor/totp', '/settings/security/totp', 301);
Route::redirect('/settings/two-factor/yubikey', '/settings/security/yubikey', 301);

Route::post('/profile/avatar/store', StoreAvatarController::class)
    ->name('profile.avatar.store');

Route::post('/settings/preferences', UpdatePreferencesController::class)
    ->name('settings.preferences.update');
