<?php

use App\Domains\Staff\Http\Controllers\DashboardController;
use App\Domains\Staff\Http\Controllers\GroupMemberController;
use App\Domains\Staff\Http\Controllers\GroupsController;
use App\Domains\Staff\Http\Controllers\GroupTeamController;

// Forward / to /dashboard
Route::redirect('/', '/dashboard');
Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::resource('groups', GroupsController::class)->only(['index', 'show', 'update', 'destroy']);
Route::resource('groups.members', GroupMemberController::class);
Route::resource('groups.teams', GroupTeamController::class)->only(['index', 'store']);
// rewrite /departments to /groups
Route::get('/departments', fn () => redirect()->route('groups.index'));
Route::get('/departments/{department}', fn ($department) => redirect()->route('staff.groups.show', $department));
