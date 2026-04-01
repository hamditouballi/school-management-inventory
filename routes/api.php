<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BonDeLivraisonController;
use App\Http\Controllers\Api\BonDeSortieController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Items
    Route::apiResource('items', ItemController::class);

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Requests
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests/unconfirmed', [RequestController::class, 'unconfirmed']);
    Route::get('/requests/my-unconfirmed', [RequestController::class, 'myUnconfirmed']);
    Route::get('/requests/{request}', [RequestController::class, 'show']);
    Route::put('/requests/{requestModel}/status', [RequestController::class, 'updateStatus']);
    Route::post('/requests/{requestModel}/fulfill', [RequestController::class, 'fulfill']);
    Route::post('/requests/{requestModel}/confirm-receipt', [RequestController::class, 'confirmReceipt']);

    // Purchase Orders
    Route::put('/purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus']);
    Route::post('/purchase-orders/{purchaseOrder}/initial-approval', [PurchaseOrderController::class, 'initialApproval']);
    Route::post('/purchase-orders/{purchaseOrder}/proposals', [PurchaseOrderController::class, 'addPropositions']);
    Route::get('/purchase-orders/{purchaseOrder}/suppliers-for-items', [PurchaseOrderController::class, 'getSuppliersForPOItems']);
    Route::post('/purchase-orders/{purchaseOrder}/final-approval', [PurchaseOrderController::class, 'finalApproval']);
    Route::post('/purchase-orders/{purchaseOrder}/proposals/reject', [PurchaseOrderController::class, 'rejectPropositions']);
    Route::post('/purchase-orders/{purchaseOrder}/split', [PurchaseOrderController::class, 'split']);
    Route::post('/purchase-orders/{purchaseOrder}/mark-delivered', [PurchaseOrderController::class, 'markDelivered']);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);

    // Bon de Livraison
    Route::get('/purchase-orders/{purchaseOrder}/bon-de-livraison', [BonDeLivraisonController::class, 'index']);
    Route::post('/purchase-orders/{purchaseOrder}/bon-de-livraison', [BonDeLivraisonController::class, 'store']);
    Route::get('/bon-de-livraison/{bonDeLivraison}', [BonDeLivraisonController::class, 'show']);
    Route::post('/bon-de-livraison/{bonDeLivraison}/confirm', [BonDeLivraisonController::class, 'confirm']);

    // Suppliers
    Route::get('/suppliers/all-with-items', [SupplierController::class, 'allWithItems']);
    Route::apiResource('suppliers', SupplierController::class);
    Route::get('/suppliers/{supplier}/items', [SupplierController::class, 'items']);
    Route::post('/suppliers/{supplier}/items', [SupplierController::class, 'addItem']);
    Route::put('/suppliers/{supplier}/items/{itemId}', [SupplierController::class, 'updateItem']);
    Route::delete('/suppliers/{supplier}/items/{itemId}', [SupplierController::class, 'removeItem']);
    Route::get('/suppliers/{supplier}/stats', [SupplierController::class, 'stats']);

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

    // Bon de Sortie
    Route::apiResource('bon-sortie', BonDeSortieController::class);
});
