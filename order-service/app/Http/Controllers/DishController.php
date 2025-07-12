<?php

namespace App\Http\Controllers;

use App\Http\Resources\DishResource;
use Illuminate\Http\Request;
use App\Models\Dish;

class DishController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {        
        return DishResource::collection(Dish::all());
    }
}
