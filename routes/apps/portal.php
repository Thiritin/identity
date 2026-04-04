<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Directory\DirectoryController;
use App\Http\Controllers\Directory\DirectoryMemberController;
use App\Http\Controllers\Directory\DirectoryTeamController;
use App\Http\Controllers\Directory\NdaController;
use App\Http\Controllers\Directory\StaffProfileController;
use App\Http\Controllers\Profile\DeleteAccountController;
use App\Http\Controllers\Profile\ExportMyDataController;
use App\Http\Controllers\Profile\MyDataController;
use App\Http\Controllers\Profile\RevokeAppConsentController;
use App\Http\Controllers\Profile\SecurityController;
use App\Http\Controllers\Profile\Settings\Apps\NotificationTypesController;
use App\Http\Controllers\Profile\Settings\AppsController;
use App\Http\Controllers\Profile\Settings\ChangeEmailController;
use App\Http\Controllers\Profile\Settings\ConfirmPasswordController;
use App\Http\Controllers\Profile\Settings\SessionController;
use App\Http\Controllers\Profile\Settings\TelegramController;
use App\Http\Controllers\Profile\Settings\TwoFactor\BackupCodesController;
use App\Http\Controllers\Profile\Settings\TwoFactor\PasskeySetupController;
use App\Http\Controllers\Profile\Settings\TwoFactor\SecurityKeySetupController;
use App\Http\Controllers\Profile\Settings\TwoFactor\TotpSetupController;
use App\Http\Controllers\Profile\Settings\TwoFactor\YubikeySetupController;
use App\Http\Controllers\Profile\Settings\UpdatePasswordController;
use App\Http\Controllers\Profile\ShowProfileController;
use App\Http\Controllers\Profile\StoreAvatarController;
use App\Http\Controllers\Profile\UpdateConventionAttendanceController;
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
Route::post('/settings/staff-profile/conventions', [UpdateConventionAttendanceController::class, 'updateOwn'])
    ->name('settings.staff-profile.conventions');

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

    /** Passkeys */
    Route::get('/settings/security/passkeys', [PasskeySetupController::class, 'index'])
        ->name('settings.security.passkeys');
    Route::get('/settings/two-factor/passkey/options', [PasskeySetupController::class, 'createOptions'])
        ->name('settings.two-factor.passkey.options');
    Route::post('/settings/two-factor/passkey/setup', [PasskeySetupController::class, 'store'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.two-factor.passkey.store');
    Route::delete('/settings/two-factor/passkey/destroy', [PasskeySetupController::class, 'destroy'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.two-factor.passkey.destroy');

    /** Security Keys */
    Route::get('/settings/security/security-keys', [SecurityKeySetupController::class, 'index'])
        ->name('settings.security.security-keys');
    Route::get('/settings/two-factor/security-key/options', [SecurityKeySetupController::class, 'createOptions'])
        ->name('settings.two-factor.security-key.options');
    Route::post('/settings/two-factor/security-key/setup', [SecurityKeySetupController::class, 'store'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.two-factor.security-key.store');
    Route::delete('/settings/two-factor/security-key/destroy', [SecurityKeySetupController::class, 'destroy'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.two-factor.security-key.destroy');

    /** Backup Codes */
    Route::post('/settings/two-factor/backup-codes/regenerate', [BackupCodesController::class, 'regenerate'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('settings.two-factor.backup-codes.regenerate');

    Route::get('/my-data/export', ExportMyDataController::class)->name('my-data.export');
    Route::delete('/my-data/account', DeleteAccountController::class)->name('my-data.delete-account');

});

/** Sessions */
Route::get('/settings/security/sessions', [SecurityController::class, 'sessions'])->name('settings.security.sessions');
Route::delete('/settings/security/sessions/{session}', [SessionController::class, 'destroy'])
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.security.sessions.destroy');
Route::delete('/settings/security/sessions', [SessionController::class, 'destroyOthers'])
    ->middleware([HandlePrecognitiveRequests::class])
    ->name('settings.security.sessions.destroy-others');

Route::post('/settings/security/confirm-password', ConfirmPasswordController::class)
    ->name('settings.security.confirm-password');

Route::get('/settings/update-password', fn () => redirect('/settings/security', 301));
Route::redirect('/settings/two-factor', '/settings/security', 301);
Route::redirect('/settings/two-factor/totp', '/settings/security/totp', 301);
Route::redirect('/settings/two-factor/yubikey', '/settings/security/yubikey', 301);

Route::post('/profile/avatar/store', StoreAvatarController::class)
    ->name('profile.avatar.store');

/** Telegram */
Route::post('/settings/telegram/link-code', [TelegramController::class, 'generateCode'])
    ->middleware('throttle:5,1')
    ->name('settings.telegram.generate-code');
Route::get('/settings/telegram/status', [TelegramController::class, 'status'])
    ->name('settings.telegram.status');
Route::delete('/settings/telegram', [TelegramController::class, 'disconnect'])
    ->name('settings.telegram.disconnect');

Route::post('/settings/preferences', UpdatePreferencesController::class)
    ->name('settings.preferences.update');

Route::get('/my-data', MyDataController::class)->name('my-data');
Route::delete('/my-data/apps/{clientId}', RevokeAppConsentController::class)->name('my-data.revoke-app');

/** Developers */
Route::get('/developers', [AppsController::class, 'index'])->name('developers.index');

Route::middleware('developer')->prefix('developers')->name('developers.')->group(function () {
    Route::get('/create', [AppsController::class, 'create'])->name('create');
    Route::post('/', [AppsController::class, 'store'])->name('store');
    Route::get('/{app}', [AppsController::class, 'show'])->name('show');
    Route::get('/{app}/edit', [AppsController::class, 'edit'])->name('edit');
    Route::put('/{app}', [AppsController::class, 'update'])->name('update');
    Route::delete('/{app}', [AppsController::class, 'destroy'])->name('destroy');
    Route::post('/{app}/regenerate-secret', [AppsController::class, 'regenerateSecret'])->name('regenerate-secret');

    Route::get('/{app}/notification-types', [NotificationTypesController::class, 'index'])->name('notification-types.index');
    Route::post('/{app}/notification-types', [NotificationTypesController::class, 'store'])->name('notification-types.store');
    Route::put('/{app}/notification-types/{type}', [NotificationTypesController::class, 'update'])->name('notification-types.update');
    Route::delete('/{app}/notification-types/{type}', [NotificationTypesController::class, 'destroy'])->name('notification-types.destroy');
    Route::post('/{app}/notification-types/{type}/disable', [NotificationTypesController::class, 'disable'])->name('notification-types.disable');
});

/** Directory (staff only) */
Route::middleware('groupmember:staff')
    ->prefix('directory')
    ->name('directory.')
    ->group(function () {
        Route::get('/', [DirectoryController::class, 'index'])->name('index');
        Route::post('/members/{user:hashid}/nda/check', [NdaController::class, 'check'])
            ->name('members.nda.check');
        Route::post('/members/{user:hashid}/nda/send', [NdaController::class, 'send'])
            ->name('members.nda.send');

        Route::post('/g/{group:hashid}', [DirectoryController::class, 'update'])->name('update');
        Route::delete('/g/{group:hashid}', [DirectoryController::class, 'destroy'])->name('destroy');
        Route::post('/g/{group:hashid}/members', [DirectoryMemberController::class, 'store'])->name('members.store');
        Route::patch('/g/{group:hashid}/members/{user:hashid}', [DirectoryMemberController::class, 'update'])->name('members.update');
        Route::delete('/g/{group:hashid}/members/{user:hashid}', [DirectoryMemberController::class, 'destroy'])->name('members.destroy');
        Route::post('/g/{group:hashid}/teams', [DirectoryTeamController::class, 'store'])->name('teams.store');

        Route::get('/{slug}/members/{user:hashid}', [StaffProfileController::class, 'show'])
            ->where('slug', '.*')
            ->name('members.show');
        Route::post('/{slug}/members/{user:hashid}/conventions', [UpdateConventionAttendanceController::class, 'updateForUser'])
            ->where('slug', '.*')
            ->name('members.conventions');
        Route::get('/{slug}', [DirectoryController::class, 'show'])->where('slug', '.*')->name('show');
    });
