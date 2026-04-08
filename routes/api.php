<?php

use App\Http\Controllers\Api\v1\ConventionController;
use App\Http\Controllers\Api\v1\GroupController;
use App\Http\Controllers\Api\v1\GroupUserController;
use App\Http\Controllers\Api\v1\IntrospectionController;
use App\Http\Controllers\Api\v1\UserinfoController;
use App\Http\Controllers\Api\v2\ConventionController as V2ConventionController;
use App\Http\Controllers\Api\v2\GroupController as V2GroupController;
use App\Http\Controllers\Api\v2\GroupMemberController;
use App\Http\Controllers\Api\v2\IntrospectionController as V2IntrospectionController;
use App\Http\Controllers\Api\v2\Users as Users;
use App\Http\Controllers\Api\v2\NotificationController;
use App\Http\Controllers\Api\v2\StaffController;
use App\Http\Controllers\Api\v2\UserinfoController as V2UserinfoController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('api')->prefix('v1/')->name('api.v1.')->group(function () {
    /**
     * Authed
     */
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('userinfo', UserinfoController::class)->name('userinfo');
        Route::apiResource('groups', GroupController::class);
        Route::apiResource('groups.users', GroupUserController::class, [
            'only' => ['index', 'store', 'destroy'],
        ]);
    });
    /**
     * Routes not requiring authentication/doing their own authentication
     */
    // Introspect requires auth via client id + secret
    Route::post('introspect', IntrospectionController::class)->name('introspect');

    /**
     * Public routes
     */
    Route::get('conventions', [ConventionController::class, 'index'])->name('conventions.index');
    Route::get('conventions/current', [ConventionController::class, 'current'])->name('conventions.current');
});

Route::middleware('api')->prefix('v2/')->name('api.v2.')->group(function () {
    Route::middleware('auth:api')->group(function () {
        // Userinfo
        Route::get('userinfo', V2UserinfoController::class)->name('userinfo');

        // Staff
        Route::get('staff/me', [StaffController::class, 'me'])->name('staff.me');
        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('staff/{user}', [StaffController::class, 'show'])->name('staff.show');

        // Groups
        Route::get('groups/tree', [V2GroupController::class, 'tree'])->name('groups.tree');
        Route::get('groups', [V2GroupController::class, 'index'])->name('groups.index');
        Route::post('groups', [V2GroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{group}', [V2GroupController::class, 'show'])->name('groups.show');
        Route::put('groups/{group}', [V2GroupController::class, 'update'])->name('groups.update');
        Route::delete('groups/{group}', [V2GroupController::class, 'destroy'])->name('groups.destroy');

        // Group Members
        Route::get('groups/{group}/members', [GroupMemberController::class, 'index'])->name('groups.members.index');
        Route::post('groups/{group}/members', [GroupMemberController::class, 'store'])->name('groups.members.store');
        Route::patch('groups/{group}/members/{user}', [GroupMemberController::class, 'update'])->name('groups.members.update');
        Route::delete('groups/{group}/members/{user}', [GroupMemberController::class, 'destroy'])->name('groups.members.destroy');

        // Notifications
        Route::post('notifications', [NotificationController::class, 'store'])
            ->name('notifications.store')
            ->middleware('throttle:notifications');

        // Users
        Route::prefix('users/{user}')->where(['user' => '[a-zA-Z0-9]+'])->group(function () {
            Route::get('/', [Users\UserController::class, 'show'])->name('users.show');
            Route::get('/groups', [Users\UserGroupController::class, 'index'])->name('users.groups.index');
            Route::get('/attendances', [Users\UserAttendanceController::class, 'index'])->name('users.attendances.index');
            Route::post('/attendances', [Users\UserAttendanceController::class, 'store'])->name('users.attendances.store');
            Route::put('/attendances/{attendance}', [Users\UserAttendanceController::class, 'update'])->name('users.attendances.update');
            Route::delete('/attendances/{attendance}', [Users\UserAttendanceController::class, 'destroy'])->name('users.attendances.destroy');
            Route::get('/metadata', [Users\UserMetadataController::class, 'index'])->name('users.metadata.index');
            Route::get('/metadata/{key}', [Users\UserMetadataController::class, 'show'])->name('users.metadata.show')->where('key', '[a-zA-Z0-9._-]+');
            Route::put('/metadata/{key}', [Users\UserMetadataController::class, 'upsert'])->name('users.metadata.upsert')->where('key', '[a-zA-Z0-9._-]+');
            Route::delete('/metadata/{key}', [Users\UserMetadataController::class, 'destroy'])->name('users.metadata.destroy')->where('key', '[a-zA-Z0-9._-]+');
            Route::get('/staff', [Users\UserStaffController::class, 'show'])->name('users.staff.show');
            Route::put('/staff', [Users\UserStaffController::class, 'update'])->name('users.staff.update');
        });
    });

    // Introspect (own auth via client credentials)
    Route::post('introspect', V2IntrospectionController::class)->name('introspect');

    // Public routes
    Route::get('conventions', [V2ConventionController::class, 'index'])->name('conventions.index');
    Route::get('conventions/current', [V2ConventionController::class, 'current'])->name('conventions.current');
});

// Health check endpoint - no authentication required
Route::get('health', HealthController::class)->name('health');

// Telegram Bot Webhook
Route::post('/telegram/webhook', function () {
    app(Nutgram::class)->run();

    return response()->json(['ok' => true]);
});
