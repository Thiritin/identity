<?php

use App\Http\Controllers\Staff\DashboardController;

Route::get('/dashboard', DashboardController::class)->name('dashboard');
