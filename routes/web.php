<?php

use App\Http\Controllers\Auth\ConsentController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
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
Route::prefix('auth')->name('auth.')->middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'view'])->name('login.view');
    Route::post('login', [LoginController::class, 'submit'])->name('login.submit');
    Route::get('consent', ConsentController::class)->name('consent');
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

Route::inertia('verify', 'Auth/VerifyEmail')->name('verification.notice')->middleware(['auth']);
Route::get('verify/{id}/{hash}', \App\Http\Controllers\VerifyEmailController::class)->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('resend', \App\Http\Controllers\ResendVerificationEmailController::class)->middleware(['auth', 'throttle:6,1'])->name('verification.send');


Route::prefix('auth')->middleware('auth')->name('auth.')->group(function () {
    Route::post('logout', function () {
       Auth::logout();
       return Redirect::route('auth.login.view');
    })->name('logout');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

