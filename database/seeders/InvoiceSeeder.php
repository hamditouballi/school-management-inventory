<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $financeManager = User::where('role', 'finance_manager')->first();
        $items = Item::take(5)->get();

        $types = ['incoming', 'return'];
        $suppliers = ['Office Supplies Co.', 'School Essentials Ltd.', 'Premium Stationery', 'Educational Materials Inc.'];

        for ($i = 0; $i < 10; $i++) {
            $type = $types[array_rand($types)];
            $invoice = Invoice::create([
                'type' => $type,
                'supplier' => $suppliers[array_rand($suppliers)],
                'date' => now()->subDays(rand(1, 30)),
                'id_responsible_finance' => $financeManager->id,
            ]);

            $itemCount = rand(1, 3);

            for ($j = 0; $j < $itemCount; $j++) {
                $item = $items->random();
                $quantity = rand(5, 50);
                $unitPrice = $item->price ?? rand(1, 10);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $item->designation,
                    'description' => $item->description,
                    'quantity' => $quantity,
                    'unit' => $item->unit,
                    'unit_price' => $unitPrice,
                ]);
            }
        }
    }
}
