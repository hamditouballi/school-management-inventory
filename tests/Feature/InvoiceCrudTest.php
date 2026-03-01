<?php

use App\Models\Invoice;
use App\Models\User;
use App\Models\Item;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'finance_manager']);
});

test('it can list all invoices', function () {
    Invoice::factory()->count(3)->create();

    actingAs($this->user, 'sanctum')
        ->getJson('/api/invoices')
        ->assertStatus(200)
        ->assertJsonCount(3);
});

test('it can create a new invoice with items', function () {
    $itemData = [
        'item_name' => 'Test Item',
        'quantity' => 10,
        'unit_price' => 100,
        'unit' => 'pcs'
    ];

    $payload = [
        'supplier' => 'Test Supplier',
        'type' => 'incoming',
        'date' => now()->format('Y-m-d'),
        'items' => [$itemData]
    ];

    actingAs($this->user, 'sanctum')
        ->postJson('/api/invoices', $payload)
        ->assertStatus(201)
        ->assertJsonPath('supplier', 'Test Supplier');

    $this->assertDatabaseHas('invoices', ['supplier' => 'Test Supplier']);
    $this->assertDatabaseHas('items', ['designation' => 'Test Item', 'quantity' => 10]);
});

test('it returns validation errors when creating an invoice with invalid data', function () {
    $payload = [
        'supplier' => '', // Required
        'type' => 'invalid_type', // Must be incoming or return
        'items' => [] // Min 1
    ];

    actingAs($this->user, 'sanctum')
        ->postJson('/api/invoices', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['supplier', 'type', 'items']);
});

test('it can show a specific invoice', function () {
    $invoice = Invoice::factory()->create();

    actingAs($this->user, 'sanctum')
        ->getJson("/api/invoices/{$invoice->id}")
        ->assertStatus(200)
        ->assertJsonPath('id', $invoice->id);
});

test('it returns 404 when showing a non-existent invoice', function () {
    actingAs($this->user, 'sanctum')
        ->getJson("/api/invoices/999")
        ->assertStatus(404);
});

test('it can update an existing invoice', function () {
    $invoice = Invoice::factory()->create(['supplier' => 'Old Supplier']);
    
    $payload = [
        'supplier' => 'New Supplier',
        'type' => 'incoming',
        'date' => now()->format('Y-m-d'),
        'items' => [
            ['item_name' => 'Updated Item', 'quantity' => 5, 'unit_price' => 50]
        ]
    ];

    actingAs($this->user, 'sanctum')
        ->putJson("/api/invoices/{$invoice->id}", $payload)
        ->assertStatus(200)
        ->assertJsonPath('supplier', 'New Supplier');

    $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'supplier' => 'New Supplier']);
});

test('it can delete an invoice', function () {
    $invoice = Invoice::factory()->create();

    actingAs($this->user, 'sanctum')
        ->deleteJson("/api/invoices/{$invoice->id}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
});
