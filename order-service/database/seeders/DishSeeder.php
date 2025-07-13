<?php

namespace Database\Seeders;

use App\Models\Dish;
use Illuminate\Database\Seeder;

class DishSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dishesList = [
            [
                'name' => "Паста Карбонара",
                'recipe' => fake()->sentence(10, true),
                'image' => fake()->imageUrl(),
                'price' => 100,
                'article' => 4998,
            ],
            [
                'name' => "Паста Балоньезе",
                'recipe' => fake()->sentence(10, true),
                'image' => fake()->imageUrl(),
                'price' => 100,
                'article' => fake()->unique()->numberBetween(50, 500),
            ],
            [
                'name' => "Паста с курицей и грибами",
                'recipe' => fake()->sentence(10, true),
                'image' => fake()->imageUrl(),
                'price' => 100,
                'article' => fake()->unique()->numberBetween(50, 500),
            ],
            [
                'name' => "Салат Цезарь с курицей",
                'recipe' => fake()->sentence(10, true),
                'image' => fake()->imageUrl(),
                'price' => 100,
                'article' => fake()->unique()->numberBetween(50, 500),
            ],
            [
                'name' => "Салат Нисуаз с лососем",
                'recipe' => fake()->sentence(10, true),
                'image' => fake()->imageUrl(),
                'price' => 100,
                'article' => fake()->unique()->numberBetween(50, 500),
            ],
            [
                'name' => "Торт Наполеон",
                'recipe' => fake()->sentence(10, true),
                'image' => fake()->imageUrl(),
                'price' => 100,
                'article' => fake()->unique()->numberBetween(50, 500),
            ],
            [
                'name' => "Блинчики 10 шт",
                'recipe' => fake()->sentence(10, true),
                'image' => fake()->imageUrl(),
                'price' => 100,
                'article' => fake()->unique()->numberBetween(50, 500),
            ],
            [
                'name' => "Блинчики с грибами и сыром",
                'recipe' => fake()->sentence(10, true),
                'image' => fake()->imageUrl(),
                'price' => 100,
                'article' => fake()->unique()->numberBetween(50, 500),
            ] 
        ];
        Dish::insert($dishesList);
    }
}
