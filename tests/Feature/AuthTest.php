<?php

use App\Models\User;
use function Pest\Laravel\{post, defaultHeaders, get, actingAs};

beforeEach(function () {
    $this->user = User::factory()->create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'role' => 'stock_manager', // 'admin' was not in enum
    ]);
});

test('it allows a user to login with valid credentials', function () {
    $this->withoutExceptionHandling();
    $response = post('/login', [
        'username' => 'testuser',
        'password' => 'password123',
    ]);

    // Check if it redirects to dashboard or intended URL
    $response->assertStatus(302);
    $this->assertAuthenticatedAs($this->user);
});

test('it rejects login with invalid credentials', function () {
    $response = post('/login', [
        'username' => 'testuser',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('it prevents unauthorized users from accessing protected routes', function () {
    // Assuming /dashboard is protected
    $response = get('/dashboard');
    $response->assertRedirect('/login');
});

test('it allows an authenticated user to access protected routes', function () {
    actingAs($this->user)
        ->get('/dashboard')
        ->assertStatus(200); // Or 302 if it redirects inside
});

test('it ensures role-based isolation works correctly', function () {
    // Create a teacher user
    $teacher = User::factory()->create(['role' => 'teacher']);

    // Attempt to access the dashboard (which is restricted for teachers)
    $response = actingAs($teacher)->get('/dashboard');

    // Should redirect to requests.page
    $response->assertRedirect('/requests');
});
