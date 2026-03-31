<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'designation' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'quantity' => fake()->numberBetween(10, 500),
            'unit' => fake()->randomElement(['pcs', 'kg', 'box', 'unit']),
            'low_stock_threshold' => 20,
            'image_path' => null,
        ];
    }
}
