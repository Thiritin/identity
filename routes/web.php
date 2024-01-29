<?php

use App\Http\Controllers\Auth\ChooseController;
use App\Http\Controllers\Auth\ConsentController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\FrontChannelLogoutController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('login', [LoginController::class, 'view'])->name(
        'login.view'
    );
    Route::post('login', [LoginController::class, 'submit'])->name('login.submit');

    Route::get('two-factor',
        [\App\Http\Controllers\TwoFactorController::class, 'show'])
        ->middleware('signed')->name('two-factor');

    Route::post('two-factor',
        [\App\Http\Controllers\TwoFactorController::class, 'submit'])
        ->middleware('signed')->name('two-factor.submit');

    Route::get('consent', ConsentController::class)->name('consent');
    Route::get('logout', LogoutController::class)->name('logout');

    Route::middleware('guest:web')->group(function () {
        Route::get('choose', ChooseController::class)->name('choose');
        Route::redirect('choose/login', '/auth/portal/login', 301)->name('oidc.login');
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

    // OIDC Frontchannel Logout
    Route::get('frontchannel-logout', FrontChannelLogoutController::class)->middleware(['auth'])->name(
        'frontchannel_logout'
    );
});

// Error
Route::get('auth/error', App\Http\Controllers\Auth\ErrorController::class)->name('auth.error');

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
    ])->middleware(['auth'])->name('verification.send');

    Route::get('verify/{id}/{hash}', [
        VerifyEmailController::class,
        'verify',
    ])->name('verification.verify');
});

Route::get('/', function () {
    return Redirect::route('dashboard');
});
