<?php

use App\Models\Item;
use App\Models\Proposition;
use App\Models\PropositionGroup;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->hrManager = User::factory()->create(['role' => 'hr_manager']);
});

test('hr manager full workflow scenario', function () {
    step('Create Request', function () use (&$requestId) {
        $item = Item::factory()->create(['designation' => 'HR Laptop', 'quantity' => 0]);
        $requestData = [
            'items' => [
                ['item_id' => $item->id, 'quantity_requested' => 1],
            ],
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
            'status' => 'pending_initial_approval',
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

    step('Review supplier proposals', function () use (&$po, &$proposalGroupId) {
        $supplier = \App\Models\Supplier::factory()->create(['name' => 'Tech Supply Co']);
        $item = Item::factory()->create();

        $proposalGroupId = (string) Str::uuid();

        PropositionGroup::factory()->create([
            'id' => $proposalGroupId,
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
            'proposition_order' => 0,
        ]);

        $proposal = Proposition::factory()->create([
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'quantity' => 10,
            'unit_price' => 150.00,
            'proposition_group_id' => $proposalGroupId,
        ]);

        actingAs($this->hrManager, 'sanctum')
            ->getJson("/api/purchase-orders/{$po->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Tech Supply Co']);
    });

    step('Select final supplier / Final Approval', function () use (&$po, &$proposalGroupId) {
        $po->update(['status' => 'pending_final_approval']);

        actingAs($this->hrManager, 'sanctum')
            ->postJson("/api/purchase-orders/{$po->id}/final-approval", ['selected_group_id' => $proposalGroupId])
            ->assertStatus(200);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'status' => 'final_approved',
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
