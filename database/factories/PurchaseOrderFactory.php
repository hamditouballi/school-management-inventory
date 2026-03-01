<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'date' => now(),
            'id_responsible_stock' => User::factory(),
            'status' => 'pending_initial_approval',
            'supplier' => 'Default Supplier',
            'total_amount' => 0,
        ];
    }
}
