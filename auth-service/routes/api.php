<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;


Route::get('/health', function (Request $request) {
    return response()->json([
        "status" => "OK"
    ], 200);
});

Route::get('/ready', function (Request $request) {
    return response()->json([
        "status" => "OK"
    ], 200);
});

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);        
        Route::get('check', [AuthController::class, 'validateToken']);

        Route::post('me', [AuthController::class, 'me']);
        Route::put('me', [UserController::class, 'update']);
        Route::delete('me', [UserController::class, 'destroy']);
    });
});