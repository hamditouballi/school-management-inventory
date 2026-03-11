<?php

use App\Models\User;
use App\Models\Item;
use function Pest\Laravel\{actingAs, postJson, getJson, deleteJson};

beforeEach(function () {
    $this->director = User::factory()->create(['role' => 'director']);
});

test('director full workflow scenario', function () {
    step('Create item request', function () use (&$requestId) {
        $item = Item::factory()->create(['designation' => 'Pencil', 'quantity' => 100]);
        $requestData = [
            'items' => [
                ['item_id' => $item->id, 'quantity_requested' => 10]
            ]
        ];

        $response = actingAs($this->director, 'sanctum')
            ->postJson('/api/requests', $requestData)
            ->assertStatus(201);

        $requestId = $response->json('id');
    });

    step('View request status', function () use (&$requestId) {
        actingAs($this->director, 'sanctum')
            ->getJson("/api/requests/{$requestId}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'pending');
    });

    step('Attempt unauthorized action', function () {
        $item = Item::factory()->create();
        actingAs($this->director, 'sanctum')
            ->deleteJson("/api/items/{$item->id}")
            ->assertStatus(403);
    });

    step('Logout', function () {
        actingAs($this->director, 'sanctum')
            ->postJson('/api/logout')
            ->assertStatus(200);
    });
});

