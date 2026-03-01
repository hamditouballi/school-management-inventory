<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Items
    Route::apiResource('items', ItemController::class);

    // Requests
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests/{request}', [RequestController::class, 'show']);
    Route::put('/requests/{requestModel}/status', [RequestController::class, 'updateStatus']);
    Route::post('/requests/{requestModel}/fulfill', [RequestController::class, 'fulfill']);

    // Purchase Orders
    Route::put('/purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus']);
    Route::post('/purchase-orders/{purchaseOrder}/initial-approval', [PurchaseOrderController::class, 'initialApproval']);
    Route::post('/purchase-orders/{purchaseOrder}/proposals', [PurchaseOrderController::class, 'addProposals']);
    Route::post('/purchase-orders/{purchaseOrder}/final-approval', [PurchaseOrderController::class, 'finalApproval']);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);

    // Statistics & Dashboard
    Route::get('/stats/consumption', [StatsController::class, 'consumption']);
    Route::get('/stats/consumption-by-department', [StatsController::class, 'consumptionByDepartment']);
    Route::get('/stats/spending', [StatsController::class, 'spending']);
    Route::get('/stats/top-items', [StatsController::class, 'topItems']);
    Route::get('/stats/low-stock', [StatsController::class, 'lowStock']);
    Route::get('/stats/dashboard', [StatsController::class, 'dashboard']);
    
    // Reports
    Route::get('/reports/consumed-materials', [ReportController::class, 'consumedMaterials']);
    Route::get('/reports/department-consumption', [ReportController::class, 'departmentConsumption']);
});
