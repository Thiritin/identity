<?php

use App\Http\Controllers\Api\v1\ConventionController;
use App\Http\Controllers\Api\v1\GroupController;
use App\Http\Controllers\Api\v1\GroupUserController;
use App\Http\Controllers\Api\v1\IntrospectionController;
use App\Http\Controllers\Api\v1\UserinfoController;
use App\Http\Controllers\Api\v2\MetadataController;
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
        Route::get('metadata', [MetadataController::class, 'index'])->name('metadata.index');
        Route::get('metadata/{key}', [MetadataController::class, 'show'])->name('metadata.show')->where('key', '[a-zA-Z0-9._-]+');
        Route::put('metadata/{key}', [MetadataController::class, 'upsert'])->name('metadata.upsert')->where('key', '[a-zA-Z0-9._-]+');
        Route::delete('metadata/{key}', [MetadataController::class, 'destroy'])->name('metadata.destroy')->where('key', '[a-zA-Z0-9._-]+');
    });
});

// Health check endpoint - no authentication required
Route::get('health', HealthController::class)->name('health');

// Telegram Bot Webhook
Route::post('/telegram/webhook', function () {
    app(Nutgram::class)->run();

    return response()->json(['ok' => true]);
});
