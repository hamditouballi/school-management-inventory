<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check() && auth()->user()->role === 'director') {
        return redirect()->route('requests.page');
    }

    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Locale switching
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Auth routes
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout')->middleware('auth:web');

// Protected routes
Route::middleware('auth:web')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/items', function () {
        return view('items.index');
    })->name('items.page');

    Route::get('/requests', function () {
        return view('requests.index');
    })->name('requests.page');

    Route::get('/purchase-orders', function () {
        return view('purchase-orders.index');
    })->name('purchase-orders.page');

    Route::get('/invoices', function () {
        return view('invoices.index');
    })->name('invoices.page');

    Route::get('/bon-sortie', function () {
        return view('bon-sortie.index');
    })->name('bon-sortie.page');

    Route::get('/notifications', function () {
        return view('notifications.index');
    })->name('notifications.page');

    // Notifications API (JSON endpoints for AJAX)
    Route::get('/api/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/api/notifications/recent', [App\Http\Controllers\Api\NotificationController::class, 'recent']);
    Route::get('/api/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/api/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/api/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
});
