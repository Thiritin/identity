<?php
/** @noinspection NullPointerExceptionInspection */

/** @noinspection PhpVoidFunctionResultUsedInspection */

use App\Http\Controllers\Auth\Authenticators\OidcClientController;
use App\Http\Controllers\Auth\ChooseController;
use App\Http\Controllers\Auth\ConsentController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\FrontChannelLogoutController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Profile\SecurityController;
use App\Http\Controllers\Profile\Settings\UpdatePasswordController;
use App\Http\Controllers\Profile\StoreAvatarController;
use App\Http\Controllers\Profile\UpdateProfileController;
use App\Http\Controllers\UpdateEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * AUTH
 * - GUEST
 * - NON-GUEST
 */
// GUEST
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('login', [LoginController::class, 'view'])->name(
        'login.view'
    ); // Must be accessible as a logged in user.
    Route::post('login', [LoginController::class, 'submit'])->name('login.submit');
    Route::get('callback', [OidcClientController::class, 'callback'])->name('oidc.callback');
    Route::get('consent', ConsentController::class)->name('consent');
    Route::get('logout', LogoutController::class)->name('logout');

    Route::middleware('guest:web')->group(function () {
        Route::get('choose', ChooseController::class)->name('choose');
        Route::get('choose/login', [OidcClientController::class, 'login'])->name('oidc.login');
        // Register
        Route::inertia('register', 'Auth/Register')->name('register.view');
        Route::post('register', RegisterController::class)->middleware('guest')->name('register.store');
        // Password Reset
        Route::inertia('forgot-password', 'Auth/ForgotPassword')->name('forgot-password.view');
        Route::post('forgot-password', ForgotPasswordController::class)->name('forgot-password.store');
        // Set new Password
        Route::get('password-reset', [PasswordResetController::class, 'view'])->name('password-reset.view');
        Route::post('password-reset', [PasswordResetController::class, 'store'])->name('password-reset.store');
    });

    // Error
    Route::get('error', App\Http\Controllers\Auth\ErrorController::class)->name('error');

    // OIDC Frontchannel Logout
    Route::get('frontchannel-logout', FrontChannelLogoutController::class)->middleware(['auth'])->name(
        'frontchannel_logout'
    );
});

// E-Mail First Sign Up
Route::prefix('auth')->group(function () {

    Route::get('verify', [VerifyEmailController::class, 'view'])->middleware('auth')->name('verification.notice');
    Route::get('verify/logout', [
        VerifyEmailController::class,
        'logout',
    ])->middleware('auth')->name('verification.logout');
    Route::post('verify', [
        VerifyEmailController::class,
        'resend',
    ])->middleware(['throttle:6,1', 'auth'])->name('verification.send');

    Route::get('verify/{id}/{hash}', [
        VerifyEmailController::class,
        'verify',
    ])->name('verification.verify');
});

Route::get('/', function () {
    return Redirect::route('dashboard');
});

// General Routes
Route::middleware(['auth', 'verified', 'auth.oidc'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::inertia('/profile', 'Profile/Show')->name('profile');
    Route::inertia('/settings/profile', 'Settings/Profile')->name('settings.profile');
    Route::post('/settings/profile/update', UpdateProfileController::class)->name('settings.update-profile.update');
    Route::get('/settings/profile/update/email', UpdateEmailController::class)->name(
        'settings.update-profile.email.update'
    )->middleware('signed');

    Route::inertia('/settings/update-password', 'Settings/UpdatePassword')->name('settings.update-password');
    Route::post('/settings/update-password', UpdatePasswordController::class)->name('settings.update-password.store');

    Route::inertia('/settings/two-factor', 'Settings/TwoFactor')->name('settings.two-factor');
    Route::get('/security', [SecurityController::class, 'index'])->name('security');

    Route::inertia('/settings/sessions', 'Settings/TwoFactor')->name('settings.sessions');

    Route::post('/profile/avatar/store', StoreAvatarController::class)->name('profile.avatar.store');

    Route::resource('groups', GroupController::class);
});

/**
 * Admin
 */
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', [OidcClientController::class, 'login'])->name('filament.auth.login');
    Route::get('/admin/callback', [OidcClientController::class, 'callback'])->name('filament.auth.callback');
});

Route::get('/admin/frontchannel-logout', \App\Http\Controllers\Admin\Auth\FrontChannelLogoutController::class)
    ->middleware('auth:admin')
    ->name('filament.auth.frontchannel-logout');
