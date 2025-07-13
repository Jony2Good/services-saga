<?php

namespace App\Http\Controllers;

use App\Models\StockAmountDishes;
use App\Http\Resources\StockResource;

class StockController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
       return StockResource::collection(StockAmountDishes::all());
    }
}
