<?php

use App\Http\Controllers\Auth\BackChannelLogoutController;
use App\Http\Controllers\Auth\ConsentController;
use App\Http\Controllers\Auth\EmailController;
use App\Http\Controllers\Auth\ErrorController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\FrontChannelLogoutController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RegisterVerifyController;
use App\Http\Controllers\Auth\RememberSessionController;
use App\Http\Controllers\Auth\VerifyCodeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UpdateEmailController;
use App\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('login', [EmailController::class, 'view'])->name('login.view');
    Route::post('login', [EmailController::class, 'submit'])
        ->middleware([HandlePrecognitiveRequests::class, ProtectAgainstSpam::class, 'throttle:15,1,login-email'])
        ->name('login.submit');
    Route::get('login/password', [LoginController::class, 'view'])->name('login.password.view');
    Route::post('login/password', [LoginController::class, 'submit'])
        ->middleware([HandlePrecognitiveRequests::class, ProtectAgainstSpam::class, 'throttle:10,1,login-password'])
        ->name('login.password.submit');
    Route::get('login/passkey/options', [LoginController::class, 'passkeyOptions'])
        ->name('login.passkey.options');
    Route::post('login/passkey/verify', [LoginController::class, 'passkeyVerify'])
        ->name('login.passkey.verify');

    Route::get('two-factor/security-key/options', [TwoFactorController::class, 'securityKeyOptions'])
        ->middleware('signed')
        ->name('two-factor.security-key.options');

    Route::get('two-factor',
        [TwoFactorController::class, 'show'])
        ->middleware('signed')->name('two-factor');

    Route::post('two-factor',
        [TwoFactorController::class, 'submit'])
        ->middleware(['signed', HandlePrecognitiveRequests::class])
        ->name('two-factor.submit');

    Route::get('remember-session', [RememberSessionController::class, 'show'])
        ->name('remember-session');
    Route::post('remember-session', [RememberSessionController::class, 'submit'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('remember-session.submit');

    Route::get('consent', [ConsentController::class, 'show'])->name('consent');
    Route::post('consent', [ConsentController::class, 'accept'])->name('consent.accept');
    Route::post('consent/deny', [ConsentController::class, 'deny'])->name('consent.deny');
    Route::get('logout', LogoutController::class)->name('logout');
    Route::inertia('logged-out', 'Auth/LoggedOut')->name('logged-out');

    Route::middleware('guest:web')->group(function () {
        Route::redirect('choose/login', '/auth/portal/login', 301)->name('oidc.login');
        Route::redirect('choose', '/', 301);
        // Register
        Route::get('register', [RegisterController::class, 'view'])->name('register.view');
        Route::post('register', RegisterController::class)
            ->middleware(['guest', HandlePrecognitiveRequests::class, ProtectAgainstSpam::class, 'throttle:5,1,register'])
            ->name('register.store');
        Route::get('register/verify', [RegisterVerifyController::class, 'view'])->name('register.verify');
        Route::post('register/verify', [RegisterVerifyController::class, 'submit'])
            ->middleware(['throttle:15,1,register-verify'])
            ->name('register.verify.submit');
        Route::get('register/code', [VerifyCodeController::class, 'view'])->name('register.code');
        Route::post('register/code', [VerifyCodeController::class, 'submit'])
            ->middleware(['throttle:5,5,register-code'])
            ->name('register.code.submit');
        Route::post('register/code/resend', [VerifyCodeController::class, 'resend'])
            ->middleware(['throttle:2,1,register-code-resend'])
            ->name('register.code.resend');
        // Password Reset
        Route::inertia('forgot-password', 'Auth/ForgotPassword')->name('forgot-password.view');
        Route::post('forgot-password', ForgotPasswordController::class)
            ->middleware([HandlePrecognitiveRequests::class, 'throttle:5,1,forgot-password'])
            ->name('forgot-password.store');
        // Set new Password
        Route::get('password-reset', [PasswordResetController::class, 'view'])->name('password-reset.view');
        Route::post('password-reset', [PasswordResetController::class, 'store'])
            ->middleware([HandlePrecognitiveRequests::class])
            ->name('password-reset.store');
    });

    // OIDC Frontchannel Logout
    Route::get('frontchannel-logout', FrontChannelLogoutController::class)->middleware(['auth'])->name(
        'frontchannel_logout'
    );

    // OIDC Backchannel Logout
    Route::post('backchannel-logout', BackChannelLogoutController::class)
        ->withoutMiddleware([PreventRequestForgery::class])
        ->middleware('throttle:60,1,backchannel-logout')
        ->name('backchannel_logout');
});

// Error
Route::get('auth/error', ErrorController::class)->name('auth.error');

// Email Verification (code-based)
Route::prefix('auth')->middleware('auth')->group(function () {
    Route::get('verify', [VerifyEmailController::class, 'view'])->name('verification.notice');
    Route::post('verify', [VerifyEmailController::class, 'submit'])
        ->middleware('throttle:10,5,verify-email')
        ->name('verification.submit');
    Route::post('verify/resend', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:5,1,verify-email-resend')
        ->name('verification.resend');
});

Route::get('/settings/profile/update/email', UpdateEmailController::class)->name(
    'settings.update-profile.email.update'
)->middleware('signed');

Route::get('/', function () {
    return Redirect::route('dashboard');
});
