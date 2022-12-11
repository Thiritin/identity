<?php

use App\Http\Controllers\Api\v1\GroupController;
use App\Http\Controllers\Api\v1\GroupUserController;
use App\Http\Controllers\UserinfoController;
use Illuminate\Support\Facades\Route;

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
    Route::get('userinfo', UserinfoController::class)->name('userinfo');
    Route::apiResource("groups", GroupController::class);
    Route::apiResource("groups.users", GroupUserController::class, [
        "only" => ["index", "store", "destroy"]
    ]);
});
