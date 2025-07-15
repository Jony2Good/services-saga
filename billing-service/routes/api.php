<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\TestController;

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
    Route::post('me', [BillingController::class, 'show']);
    Route::put('me', [BillingController::class, 'update']);   
});

Route::post('test', TestController::class);
