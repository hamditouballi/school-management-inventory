<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $stockManager = User::where('role', 'stock_manager')->first();
        $items = Item::take(5)->get();

        $statuses = [
            'pending_initial_approval',
            'initial_approved',
            'pending_final_approval',
            'final_approved',
            'rejected',
            'ordered',
        ];

        $suppliers = ['Office Supplies Co.', 'School Essentials Ltd.', 'Premium Stationery', 'Educational Materials Inc.'];

        foreach ($statuses as $index => $status) {
            $po = PurchaseOrder::create([
                'date' => now()->subDays(rand(1, 30)),
                'id_responsible_stock' => $stockManager->id,
                'status' => $status,
                'supplier' => $suppliers[array_rand($suppliers)],
                'total_amount' => 0,
            ]);

            $itemCount = rand(2, 4);
            $totalAmount = 0;

            for ($i = 0; $i < $itemCount; $i++) {
                $item = $items->random();
                $quantity = rand(10, 100);
                $unitPrice = $item->price ?? rand(1, 10);
                $subtotal = $quantity * $unitPrice;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);

                $totalAmount += $subtotal;
            }

            $po->update(['total_amount' => $totalAmount]);
        }
    }
}
