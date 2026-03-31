<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PropositionGroup;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PropositionGroupFactory extends Factory
{
    protected $model = PropositionGroup::class;

    public function definition(): array
    {
        return [
            'id' => fn () => (string) Str::uuid(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'item_id' => Item::factory(),
            'proposition_order' => 0,
        ];
    }
}
