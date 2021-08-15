<?php

use App\Http\Controllers\Auth\ConsentController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
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


Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('login', [LoginController::class, 'view'])->name('login.view');
    Route::post('login', [LoginController::class, 'submit'])->name('login.submit');
    Route::get('consent', ConsentController::class)->name('consent');
    // Register
    Route::inertia('register', 'Auth/Register')->name('register.view');
    Route::post('register', RegisterController::class)->middleware('guest')->name('register.store');
    // Password Reset
    Route::inertia('forgot-password','Auth/ForgotPassword')->name('forgot-password.view');
    Route::post('forgot-password', ForgotPasswordController::class)->name('forgot-password.store');
    // Set new Password
    Route::inertia('password-reset','Auth/PasswordReset')->name('password-reset.view');
    Route::post('password-reset', PasswordResetController::class)->name('password-reset.view');
});


Route::get('/', function () {
    return "TODO";
})->middleware('auth:sanctum', 'verified');

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

