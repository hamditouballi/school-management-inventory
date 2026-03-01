<?php

use App\Models\User;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Request as UserRequest;
use App\Models\RequestItem;
use function Pest\Laravel\{actingAs, postJson, getJson, putJson};

beforeEach(function () {
    $this->stockManager = User::factory()->create(['role' => 'stock_manager']);
});

test('stock manager full workflow scenario', function () {
    step('Add new inventory item', function () use (&$item) {
        $itemData = [
            'designation' => 'E2E Test Item',
            'description' => 'A new item added by Stock Manager',
            'quantity' => 50,
            'price' => 15.5,
            'unit' => 'pcs',
            'category' => 'Stationery',
            'low_stock_threshold' => 10
        ];

        actingAs($this->stockManager, 'sanctum')
            ->postJson('/api/items', $itemData)
            ->assertStatus(201);

        $this->assertDatabaseHas('items', ['designation' => 'E2E Test Item']);
        $item = Item::where('designation', 'E2E Test Item')->first();
    });

    step('Create purchase order', function () use (&$item, &$poId) {
        $poData = [
            'items' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 100,
                    'unit_price' => 14.0
                ]
            ],
            'date' => now()->format('Y-m-d')
        ];

        $response = actingAs($this->stockManager, 'sanctum')
            ->postJson('/api/purchase-orders', $poData)
            ->assertStatus(201);

        $poId = $response->json('id');
        $this->assertDatabaseHas('purchase_orders', ['id' => $poId, 'status' => 'pending_initial_approval']);
    });

    step('Fulfill a pending teacher request', function () use (&$item) {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $userRequest = UserRequest::factory()->create([
            'user_id' => $teacher->id,
            'status' => 'hr_approved'
        ]);
        RequestItem::factory()->create([
            'request_id' => $userRequest->id,
            'item_id' => $item->id,
            'quantity_requested' => 5
        ]);

        actingAs($this->stockManager, 'sanctum')
            ->postJson("/api/requests/{$userRequest->id}/fulfill")
            ->assertStatus(200);

        $this->assertDatabaseHas('requests', ['id' => $userRequest->id, 'status' => 'fulfilled']);
        $this->assertEquals(45, $item->refresh()->quantity);
    });

    step('Logout', function () {
        actingAs($this->stockManager, 'sanctum')
            ->postJson('/api/logout')
            ->assertStatus(200);
    });
});

