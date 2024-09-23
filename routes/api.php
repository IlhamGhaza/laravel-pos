<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);
    Route::apiResource('orders', \App\Http\Controllers\Api\OrderController::class);
    Route::get('list-categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/reports/summary', [App\Http\Controllers\Api\ReportController::class, 'summary']);
    Route::get('/reports/product-sales', [App\Http\Controllers\Api\ReportController::class, 'productSales']);
    Route::get('/reports/close-cashier', [App\Http\Controllers\Api\ReportController::class, 'closeCashier']);
});
