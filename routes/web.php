<?php

use App\Http\Controllers\Auth\ChooseController;
use App\Http\Controllers\Auth\ConsentController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResendVerificationEmailController;
use App\Http\Controllers\Auth\Authenticators\OidcClientController;
use App\Http\Controllers\Profile\SecurityController;
use App\Http\Controllers\Profile\StoreAvatarController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

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
    Route::get('login', [LoginController::class, 'view'])->name('login.view'); // Must be accessible as a logged in user.
    Route::post('login', [LoginController::class, 'submit'])->name('login.submit');
    Route::get('callback', [OidcClientController::class, 'callback'])->name('oidc.callback');
    Route::get('consent', ConsentController::class)->name('consent');

    Route::middleware('guest')->group(function () {
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
});

Route::inertia('verify', 'Auth/VerifyEmail')->name('verification.notice')->middleware(['auth']);
Route::get('verify/{id}/{hash}', \App\Http\Controllers\Auth\VerifyEmailController::class)->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('resend', ResendVerificationEmailController::class)->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::post('auth/logout', LogoutController::class)->name('auth.logout')->middleware('auth');
Route::post('auth/logout-local', LogoutController::class)->name('auth.logout.local')->middleware('auth');

Route::get('/', function () {
    return Redirect::route('dashboard');
});

// General Routes
Route::middleware('auth', 'verified')->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('/profile', 'Profile/Show')->name('profile');
    Route::inertia('/settings/profile', 'Settings/Profile')->name('settings.profile');
    Route::inertia('/settings/update-password', 'Settings/UpdatePassword')->name('settings.update-password');
    Route::inertia('/settings/two-factor', 'Settings/TwoFactor')->name('settings.two-factor');
    Route::get('/security', [SecurityController::class,'index'])->name('security');

    Route::post('/profile/avatar/store', StoreAvatarController::class)->name('profile.avatar.store');
});



