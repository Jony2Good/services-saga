<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\StockAmountDishes;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockAmountDishes>
 */
class StockAmountDishesFactory extends Factory
{
     /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = StockAmountDishes::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1,10),
        ];
    }
}
