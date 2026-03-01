<?php

namespace Database\Factories;

use App\Models\BonDeSortie;
use App\Models\Request;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BonDeSortieFactory extends Factory
{
    protected $model = BonDeSortie::class;

    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(1, 50),
            'date' => now(),
            'id_responsible_stock' => User::factory(),
        ];
    }
}
