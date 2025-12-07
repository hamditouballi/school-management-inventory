<?php

use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check() && auth()->user()->role === 'teacher') {
        return redirect()->route('requests.index');
    }
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Locale switching
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Auth routes
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Placeholder routes for navigation (implement as needed)
    Route::get('/items', function() { return view('items.index'); })->name('items.index');
    Route::get('/requests', function() { return view('requests.index'); })->name('requests.index');
    Route::get('/purchase-orders', function() { return view('purchase-orders.index'); })->name('purchase-orders.index');
    Route::get('/invoices', function() { return view('invoices.index'); })->name('invoices.index');
});
