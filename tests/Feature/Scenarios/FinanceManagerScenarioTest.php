<?php

use App\Models\User;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\{actingAs, postJson, getJson};

beforeEach(function () {
    $this->financeManager = User::factory()->create(['role' => 'finance_manager']);
    Storage::fake('public');
});

test('finance manager full workflow scenario', function () {
    step('Create Request', function () {
        $item = Item::factory()->create(['designation' => 'Office Paper', 'quantity' => 10]);
        $requestData = [
            'items' => [
                ['item_id' => $item->id, 'quantity_requested' => 5]
            ]
        ];
        
        actingAs($this->financeManager, 'sanctum')
            ->postJson('/api/requests', $requestData)
            ->assertStatus(201);
    });

    step('Generate Invoice from PO', function () {
        $item = Item::factory()->create();
        $po = PurchaseOrder::factory()->create([
            'status' => 'final_approved',
            'supplier' => 'Stationery Hub',
            'total_amount' => 500.00
        ]);
        $poItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
            'quantity' => 100,
            'unit_price' => 5.00
        ]);

        $invoiceFromPoData = [
            'supplier' => 'Stationery Hub',
            'type' => 'incoming',
            'date' => now()->format('Y-m-d'),
            'purchase_order_id' => $po->id,
            'id_purchase_order_item' => $poItem->id,
            'items' => [
                [
                    'item_id' => $item->id,
                    'item_name' => 'Office Paper',
                    'quantity' => 100,
                    'unit_price' => 5.00,
                    'unit' => 'box'
                ]
            ]
        ];

        actingAs($this->financeManager, 'sanctum')
            ->postJson('/api/invoices', $invoiceFromPoData)
            ->assertStatus(201);

        $this->assertDatabaseHas('invoices', ['id_purchase_order' => $po->id]);
    });

    step('Create manual invoice with image upload', function () {
        $manualInvoiceData = [
            'supplier' => 'Manual Supplier',
            'type' => 'incoming',
            'date' => now()->format('Y-m-d'),
            'items' => json_encode([
                [
                    'item_name' => 'New Desk',
                    'quantity' => 1,
                    'unit_price' => 250.00,
                    'unit' => 'pcs'
                ]
            ]),
            'image' => UploadedFile::fake()->image('invoice.jpg')
        ];

        actingAs($this->financeManager, 'sanctum')
            ->postJson('/api/invoices', $manualInvoiceData)
            ->assertStatus(201);

        $this->assertDatabaseHas('invoices', ['supplier' => 'Manual Supplier']);
        $this->assertDatabaseHas('items', ['designation' => 'New Desk']);
    });

    step('Generate financial report', function () {
        actingAs($this->financeManager, 'sanctum')
            ->getJson('/api/stats/spending')
            ->assertStatus(200);
    });

    step('Logout', function () {
        actingAs($this->financeManager, 'sanctum')
            ->postJson('/api/logout')
            ->assertStatus(200);
    });
});

