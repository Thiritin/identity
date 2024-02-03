<?php

use App\Http\Controllers\Staff\DashboardController;

Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::resource('departments', \App\Http\Controllers\Staff\DepartmentsController::class);
