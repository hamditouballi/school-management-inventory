<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['incoming', 'return']),
            'supplier' => fake()->company(),
            'date' => fake()->date(),
            'id_responsible_finance' => User::factory(),
            'file_path' => null,
            'image_path' => null,
        ];
    }
}
