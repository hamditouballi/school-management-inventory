<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Proposition;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PropositionFactory extends Factory
{
    protected $model = Proposition::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'supplier_id' => Supplier::factory(),
            'item_id' => Item::factory(),
            'quantity' => fake()->randomFloat(2, 1, 100),
            'unit_price' => fake()->randomFloat(2, 10, 500),
            'notes' => fake()->optional()->sentence(),
            'proposition_group_id' => fn () => (string) Str::uuid(),
            'proposition_order' => 0,
        ];
    }
}
