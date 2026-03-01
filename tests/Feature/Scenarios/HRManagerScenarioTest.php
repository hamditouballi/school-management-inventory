<?php

use App\Models\User;
use App\Models\Request as UserRequest;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderSupplier;
use function Pest\Laravel\{actingAs, postJson, getJson, putJson};

beforeEach(function () {
    $this->hrManager = User::factory()->create(['role' => 'hr_manager']);
});

test('hr manager full workflow scenario', function () {
    step('Create Request', function () use (&$requestId) {
        $item = Item::factory()->create(['designation' => 'HR Laptop', 'quantity' => 0]);
        $requestData = [
            'items' => [
                ['item_id' => $item->id, 'quantity_requested' => 1]
            ]
        ];
        
        $response = actingAs($this->hrManager, 'sanctum')
            ->postJson('/api/requests', $requestData)
            ->assertStatus(201);
            
        $requestId = $response->json('id');
        $this->assertDatabaseHas('requests', ['id' => $requestId, 'status' => 'pending']);
    });

    step('Approve Requests', function () use (&$requestId) {
        actingAs($this->hrManager, 'sanctum')
            ->putJson("/api/requests/{$requestId}/status", ['status' => 'hr_approved'])
            ->assertStatus(200);
            
        $this->assertDatabaseHas('requests', ['id' => $requestId, 'status' => 'hr_approved']);
    });

    step('Review pending purchase order', function () use (&$po) {
        $stockManager = User::factory()->create(['role' => 'stock_manager']);
        $po = PurchaseOrder::factory()->create([
            'id_responsible_stock' => $stockManager->id,
            'status' => 'pending_initial_approval'
        ]);

        actingAs($this->hrManager, 'sanctum')
            ->getJson("/api/purchase-orders/{$po->id}")
            ->assertStatus(200);
    });

    step('Approve initial request', function () use (&$po) {
        actingAs($this->hrManager, 'sanctum')
            ->postJson("/api/purchase-orders/{$po->id}/initial-approval", ['action' => 'approve'])
            ->assertStatus(200);
            
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'initial_approved']);
    });

    step('Review supplier proposals', function () use (&$po, &$proposal) {
        $proposal = PurchaseOrderSupplier::factory()->create([
            'purchase_order_id' => $po->id,
            'supplier_name' => 'Tech Supply Co',
            'price' => 1500.00
        ]);
        
        actingAs($this->hrManager, 'sanctum')
            ->getJson("/api/purchase-orders/{$po->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['supplier_name' => 'Tech Supply Co']);
    });

    step('Select final supplier / Final Approval', function () use (&$po, &$proposal) {
        $po->update(['status' => 'pending_final_approval']);

        actingAs($this->hrManager, 'sanctum')
            ->postJson("/api/purchase-orders/{$po->id}/final-approval", ['proposal_id' => $proposal->id])
            ->assertStatus(200);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id, 
            'status' => 'final_approved',
            'supplier' => 'Tech Supply Co'
        ]);
    });

    step('View reports', function () {
        actingAs($this->hrManager, 'sanctum')
            ->getJson('/api/reports/consumed-materials')
            ->assertStatus(200);
    });

    step('Logout', function () {
        actingAs($this->hrManager, 'sanctum')
            ->postJson('/api/logout')
            ->assertStatus(200);
    });
});

