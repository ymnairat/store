<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Middleware\CheckPermission;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Users management
    Route::apiResource('users', UserController::class);
    
    // Roles & Permissions
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    
    // Inventory routes
    Route::get('inventory', [InventoryController::class, 'index']);
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::delete('transactions/{id}', [TransactionController::class, 'destroy']);
    
    // Warehouses
    Route::apiResource('warehouses', WarehouseController::class);
    
    // Teams
    Route::apiResource('teams', TeamController::class);
    
    // Products - يجب أن تكون routes المخصصة قبل apiResource
    Route::get('products/search/by-code', [ProductController::class, 'searchByCode']);
    Route::get('products/all-for-transfer', [ProductController::class, 'getAllForTransfer']); // جميع المنتجات للنقل بين الفِرق
    Route::apiResource('products', ProductController::class);
    
    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('inventory/excel', [ReportController::class, 'exportInventoryExcel']);
        Route::get('inventory/excel/{warehouseId}', [ReportController::class, 'exportInventoryExcel']);
        Route::get('transactions/excel', [ReportController::class, 'exportTransactionsExcel']);
    });
    
    // Search
    Route::prefix('search')->group(function () {
        Route::get('products', [SearchController::class, 'searchProducts']);
        Route::get('inventory', [SearchController::class, 'searchInventory']);
        Route::get('transactions', [SearchController::class, 'searchTransactions']);
        Route::get('warehouses/{warehouseId}/details', [SearchController::class, 'getWarehouseDetails']);
    });
});

