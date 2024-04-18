<?php

use App\Http\Controllers\Staff\DashboardController;

// Forward / to /dashboard
Route::redirect('/', '/dashboard');
Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::resource('departments', \App\Http\Controllers\Staff\DepartmentsController::class);
// Route departments -> members
Route::resource('departments.members', \App\Http\Controllers\Staff\DepartmentMemberController::class);
