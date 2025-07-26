<?php

use App\Domains\Staff\Http\Controllers\DashboardController;
use App\Domains\Staff\Http\Controllers\GroupMemberController;
use App\Domains\Staff\Http\Controllers\GroupsController;
use App\Domains\Staff\Http\Controllers\GroupTeamController;
use App\Domains\Staff\Http\Controllers\GroupUserManagementController;
use App\Domains\Staff\Http\Controllers\OrganizationChartController;

// Forward / to /dashboard
Route::redirect('/', '/dashboard');
Route::get('/dashboard', DashboardController::class)->name('dashboard');

// Organization Chart
Route::get('/organization', [OrganizationChartController::class, 'index'])->name('organization.index');
Route::get('/organization/expanded', [OrganizationChartController::class, 'expanded'])->name('organization.expanded');
Route::get('/api/organization', [OrganizationChartController::class, 'getData'])->name('organization.data');

// Group User Management
Route::get('/groups/{group}/manage-users', [GroupUserManagementController::class, 'index'])->name('groups.manage-users');
Route::post('/groups/{group}/users/{user}/grant-management', [GroupUserManagementController::class, 'grantUserManagement'])->name('groups.grant-user-management');
Route::delete('/groups/{group}/users/{user}/revoke-management', [GroupUserManagementController::class, 'revokeUserManagement'])->name('groups.revoke-user-management');
Route::patch('/groups/{group}/users/{user}/level', [GroupUserManagementController::class, 'updateUserLevel'])->name('groups.update-user-level');

Route::resource('groups', GroupsController::class)->only(['index', 'show', 'update', 'destroy']);
Route::resource('groups.members', GroupMemberController::class);
Route::resource('groups.teams', GroupTeamController::class)->only(['index', 'store']);
// rewrite /departments to /groups
Route::get('/departments', fn () => redirect()->route('groups.index'));
Route::get('/departments/{department}', fn ($department) => redirect()->route('staff.groups.show', $department));
