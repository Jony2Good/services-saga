<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserNotificationController;

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

Route::prefix('v1')->group(function() {   
    Route::get('me', UserNotificationController::class);
});
