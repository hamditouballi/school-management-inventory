<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'item_name' => fake()->word(),
            'description' => fake()->sentence(),
            'quantity' => fake()->numberBetween(1, 100),
            'unit' => fake()->randomElement(['pcs', 'kg', 'box']),
            'unit_price' => fake()->randomFloat(2, 5, 500),
        ];
    }
}
