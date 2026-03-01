<?php

use App\Models\User;
use App\Models\Item;
use function Pest\Laravel\{actingAs, postJson, getJson, deleteJson};

beforeEach(function () {
    $this->teacher = User::factory()->create(['role' => 'teacher']);
});

test('teacher full workflow scenario', function () {
    step('Create item request', function () use (&$requestId) {
        $item = Item::factory()->create(['designation' => 'Pencil', 'quantity' => 100]);
        $requestData = [
            'items' => [
                ['item_id' => $item->id, 'quantity_requested' => 10]
            ]
        ];

        $response = actingAs($this->teacher, 'sanctum')
            ->postJson('/api/requests', $requestData)
            ->assertStatus(201);

        $requestId = $response->json('id');
    });

    step('View request status', function () use (&$requestId) {
        actingAs($this->teacher, 'sanctum')
            ->getJson("/api/requests/{$requestId}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'pending');
    });

    step('Attempt unauthorized action', function () {
        $item = Item::factory()->create();
        actingAs($this->teacher, 'sanctum')
            ->deleteJson("/api/items/{$item->id}")
            ->assertStatus(403);
    });

    step('Logout', function () {
        actingAs($this->teacher, 'sanctum')
            ->postJson('/api/logout')
            ->assertStatus(200);
    });
});

