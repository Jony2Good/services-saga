<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProxyController;

Route::any('/{service}/{any?}', [ProxyController::class, 'index'])
    ->where('any', '.*');
