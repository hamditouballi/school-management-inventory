<?php

namespace Database\Factories;

use App\Models\Request;
use App\Models\RequestItem;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestItemFactory extends Factory
{
    protected $model = RequestItem::class;

    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'item_id' => Item::factory(),
            'quantity_requested' => fake()->numberBetween(1, 50),
        ];
    }
}
