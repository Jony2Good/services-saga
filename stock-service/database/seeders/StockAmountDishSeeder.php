<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockAmountDishes;

class StockAmountDishSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StockAmountDishes::factory(8)->create();
    }
}
