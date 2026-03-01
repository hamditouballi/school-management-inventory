<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderSupplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderSupplierFactory extends Factory
{
    protected $model = PurchaseOrderSupplier::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'supplier_name' => fake()->company(),
            'price' => fake()->randomFloat(2, 100, 5000),
            'quality_rating' => fake()->numberBetween(1, 5),
            'notes' => fake()->sentence(),
            'is_selected' => false,
        ];
    }
}
