<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Item;
use App\Models\Request as RequestModel;
use App\Models\RequestItem;
use App\Models\BonDeSortie;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Invoice;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
         $teacherPE1 = User::where('username', 'teacher_pe1')->first();
    $teacherPE2 = User::where('username', 'teacher_pe2')->first();

    $stockManager = User::where('username', 'stock_manager')->first();
    $financeManager = User::where('username', 'finance_manager')->first();

    if (!$teacherPE1 || !$teacherPE2 || !$stockManager || !$financeManager) {
        throw new \Exception('Required demo users not found. Run UserSeeder first.');
    }

    // Create 6 sample requests
    $requests = [
        ['user' => $teacherPE1, 'status' => 'fulfilled', 'created' => now()->subDays(20)],
        ['user' => $teacherPE2, 'status' => 'fulfilled', 'created' => now()->subDays(18)],
        ['user' => $teacherPE1, 'status' => 'fulfilled', 'created' => now()->subDays(15)],
        ['user' => $teacherPE2, 'status' => 'approved', 'created' => now()->subDays(10)],
        ['user' => $teacherPE1, 'status' => 'pending', 'created' => now()->subDays(3)],
        ['user' => $teacherPE2, 'status' => 'pending', 'created' => now()->subDays(1)],
    ];

        foreach ($requests as $reqData) {
            $request = RequestModel::create([
                'user_id' => $reqData['user']->id,
                'status' => $reqData['status'],
                'dateCreated' => $reqData['created'],
                'created_at' => $reqData['created'],
            ]);

            // Add 2-3 items per request
            $itemCount = rand(2, 3);
            $items = Item::inRandomOrder()->take($itemCount)->get();
            
            foreach ($items as $item) {
                RequestItem::create([
                    'request_id' => $request->id,
                    'item_id' => $item->id,
                    'quantity_requested' => rand(5, 20),
                ]);
            }

            // Create bon_de_sorties for fulfilled requests
            if ($reqData['status'] === 'fulfilled') {
                foreach ($request->requestItems as $requestItem) {
                    BonDeSortie::create([
                        'request_id' => $request->id,
                        'item_id' => $requestItem->item_id,
                        'quantity' => $requestItem->quantity_requested,
                        'date' => $reqData['created']->addDay(),
                        'id_responsible_stock' => $stockManager->id,
                    ]);
                }
            }
        }

        // Create 4 sample purchase orders
        $pos = [
            ['supplier' => 'ABC Office Supplies', 'status' => 'approved_hr', 'date' => now()->subDays(15), 'total' => 0],
            ['supplier' => 'School Direct Ltd', 'status' => 'ordered', 'date' => now()->subDays(12), 'total' => 0],
            ['supplier' => 'Education Materials Co', 'status' => 'pending_hr', 'date' => now()->subDays(5), 'total' => 0],
            ['supplier' => 'ABC Office Supplies', 'status' => 'pending_hr', 'date' => now()->subDays(2), 'total' => 0],
        ];

        foreach ($pos as $poData) {
            $po = PurchaseOrder::create([
                'supplier' => $poData['supplier'],
                'status' => $poData['status'],
                'date' => $poData['date'],
                'id_responsible_stock' => $stockManager->id,
                'total_amount' => 0,
            ]);

            // Add 2-4 items per PO
            $itemCount = rand(2, 4);
            $items = Item::inRandomOrder()->take($itemCount)->get();
            $total = 0;

            foreach ($items as $item) {
                $quantity = rand(20, 100);
                $unitPrice = $item->price;
                $total += $quantity * $unitPrice;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);
            }

            $po->update(['total_amount' => $total]);
        }

        // Create 6 sample invoices
        $purchaseOrderItems = PurchaseOrderItem::with('item')->get();
        
        for ($i = 0; $i < 6; $i++) {
            $poItem = $purchaseOrderItems->random();
            
            Invoice::create([
                'supplier' => 'Supplier ' . chr(65 + $i),
                'description' => 'Invoice for ' . $poItem->item->designation,
                'quantity' => $poItem->quantity,
                'price' => $poItem->unit_price * $poItem->quantity,
                'date' => now()->subDays(rand(1, 20)),
                'id_responsible_finance' => $financeManager->id,
                'id_purchase_order_item' => rand(0, 1) ? $poItem->id : null,
            ]);
        }
    }
}
