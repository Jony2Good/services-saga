<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DishController;
use App\Http\Controllers\OrderPaymentController;

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
    Route::get('dishes', DishController::class);
    Route::apiResource('orders', OrderController::class);
    Route::get('payments/{id}', OrderPaymentController::class);
});