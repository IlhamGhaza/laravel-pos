<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\FertilizerController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MobileOrderSyncController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DeliveryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Categories
    Route::apiResource('categories', CategoryController::class);
    // Auth
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    // Orders
    Route::post('/mobile/orders/sync', [MobileOrderSyncController::class, 'syncAndStoreOrder']);
    Route::apiResource('orders', \App\Http\Controllers\Api\OrderController::class)->except(['destroy']);
    // Route::get('orders/invoice/{invoiceNumber}', [\App\Http\Controllers\Api\OrderController::class, 'getByInvoice']);

    // Inventory
    Route::prefix('inventory')->group(function () {
        Route::get('/status', [InventoryController::class, 'getStockStatus']);
        Route::get('/history/{productId}', [InventoryController::class, 'getStockHistory']);
        Route::post('/adjust', [InventoryController::class, 'adjustInventory']);
        // Route::get('/alerts/low-stock', [InventoryController::class, 'getLowStockAlerts']);
        // Route::get('/alerts/out-of-stock', [InventoryController::class, 'getOutOfStock']);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        // Create new payment
        Route::post('/', [PaymentController::class, 'createPayment'])
            ->name('payments.create');

        // Get available payment methods
        Route::get('/methods', [PaymentController::class, 'getPaymentMethods'])
            ->name('payments.methods');

        // Check payment status
        Route::get('/status/{paymentId}', [PaymentController::class, 'checkStatus'])
            ->where('paymentId', '[0-9]+')
            ->name('payments.status');

        // Handle payment gateway notifications
        Route::post('/notifications', [PaymentController::class, 'handlePaymentNotification'])
            ->name('payments.notifications');

        // Get payment history
        Route::get('/history', [PaymentController::class, 'paymentHistory'])
            ->name('payments.history');
    });

    // Fertilizer specific features
    Route::prefix('fertilizer')->group(function () {
        Route::get('/expiry-check', [FertilizerController::class, 'checkExpiry']);
        Route::get('/convert-unit', [FertilizerController::class, 'convertUnit']);
        Route::get('/recommendations', [FertilizerController::class, 'getRecommendations']);
        Route::post('/calculate-requirement', [FertilizerController::class, 'calculateRequirement']);
        Route::get('/units', [FertilizerController::class, 'getAvailableUnits']);
        Route::get('/area-units', [FertilizerController::class, 'getAreaUnits']);
    });

    // Sync
    Route::prefix('sync')->group(function () {
        Route::get('/last-sync', [SyncController::class, 'getLastSync']);
        Route::post('/push', [SyncController::class, 'pushChanges']);
        Route::post('/pull', [SyncController::class, 'pullChanges']);
        Route::post('/resolve-conflict', [SyncController::class, 'resolveConflict']);
        Route::post('/batch', [SyncController::class, 'batchSync']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/summary', [App\Http\Controllers\Api\ReportController::class, 'summary']);
        Route::get('/product-sales', [App\Http\Controllers\Api\ReportController::class, 'productSales']);
        Route::get('/close-cashier', [App\Http\Controllers\Api\ReportController::class, 'closeCashier']);
    });

    // Discounts
    Route::prefix('discounts')->group(function () {
        // CRUD Standard
        Route::get('/', [DiscountController::class, 'index']);
        Route::post('/', [DiscountController::class, 'store']);
        Route::get('/{id}', [DiscountController::class, 'show']);
        Route::put('/', [DiscountController::class, 'update']);
        Route::delete('/{id}', [DiscountController::class, 'destroy']);

        // Special Endpoints
        Route::get('/filter/today', [DiscountController::class, 'todayDiscounts']);
        Route::get('/mobile/sync', [DiscountController::class, 'sync']);
    });

    // Resources
    Route::apiResource('products', ProductController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('purchase-orders', PurchaseOrderController::class)->except(['destroy']);
    Route::apiResource('taxes', \App\Http\Controllers\Api\TaxController::class);
    Route::apiResource('service-charges', \App\Http\Controllers\Api\ServiceChargeController::class);

    // Deliveries
    Route::apiResource('deliveries', DeliveryController::class);
    Route::prefix('deliveries')->group(function () {
        Route::post('{delivery}/dispatch', [DeliveryController::class, 'markAsDispatched']);
        Route::post('{delivery}/deliver', [DeliveryController::class, 'markAsDelivered']);
        Route::get('{delivery}/status', [DeliveryController::class, 'getStatus']);
        Route::get('status/{status}', [DeliveryController::class, 'getByStatus']);
    });
});
