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

// Phone Upload Routes (public - no auth required)
Route::get('/phone-upload/{context}/{targetId}', [App\Http\Controllers\Api\PhoneUploadController::class, 'showPage']);
Route::post('/phone-upload', [App\Http\Controllers\Api\PhoneUploadController::class, 'upload']);

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

    Route::get('/suppliers', function () {
        return view('suppliers.index');
    })->name('suppliers.page');

    Route::get('/suppliers/{id}', function ($id) {
        return view('suppliers.show', ['supplierId' => $id]);
    })->name('suppliers.show');

    Route::get('/notifications', function () {
        return view('notifications.index');
    })->name('notifications.page');

    // Notifications API (JSON endpoints for AJAX)
    Route::get('/api/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/api/notifications/recent', [App\Http\Controllers\Api\NotificationController::class, 'recent']);
    Route::get('/api/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/api/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/api/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);

    // Phone Upload Routes (polling needs auth)
    Route::get('/phone-uploads/{sessionKey}', [App\Http\Controllers\Api\PhoneUploadController::class, 'getUploads']);
    Route::post('/phone-uploads/{uploadId}/received', [App\Http\Controllers\Api\PhoneUploadController::class, 'markAsReceived']);
    Route::post('/phone-uploads/promote', [App\Http\Controllers\Api\PhoneUploadController::class, 'promoteToMain']);
    Route::delete('/phone-uploads/{sessionKey}', [App\Http\Controllers\Api\PhoneUploadController::class, 'cleanup']);

    // Server IP endpoint for phone upload
    Route::get('/api/server-ip', function () {
        $ip = request()->getHost();
        // Try to get actual local IP
        if ($ip === 'localhost' || $ip === '127.0.0.1') {
            // Attempt to get local IP
            if (PHP_OS === 'WINNT') {
                exec('ipconfig', $output);
                foreach ($output as $line) {
                    if (preg_match('/IPv4.*?(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                        if (strpos($matches[1], '192.168.') !== false || strpos($matches[1], '10.') !== false) {
                            $ip = $matches[1];
                            break;
                        }
                    }
                }
            } else {
                exec('ip route get 1', $output);
                if (isset($output[0]) && preg_match('/(\d+\.\d+\.\d+\.\d+)/', $output[0], $matches)) {
                    $ip = $matches[1];
                }
            }
        }

        return response()->json(['ip' => $ip]);
    });
});
