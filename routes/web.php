<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;

// Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/test', function () {
    $user = auth()->user();
    dd($user->hasPermission('products.view'));
})->middleware(['auth']);

// Protected Routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::resource('products', ProductController::class);

    // Warehouses
    Route::resource('warehouses', WarehouseController::class);
    Route::get('/warehouses/{warehouse}/details', [WarehouseController::class, 'details'])->name('warehouses.details');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    // Users
    Route::resource('users', UserController::class);

    // Roles
    Route::resource('roles', RoleController::class);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/inventory/excel', [ReportController::class, 'exportInventoryExcel'])->name('reports.inventory.excel');
    Route::get('/reports/inventory/excel/{warehouse}', [ReportController::class, 'exportInventoryExcel'])->name('reports.inventory.excel.warehouse');
    Route::get('/reports/transactions/excel', [ReportController::class, 'exportTransactionsExcel'])->name('reports.transactions.excel');

    // Search
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/search/products', [SearchController::class, 'searchProducts'])->name('search.products');
    Route::get('/search/inventory', [SearchController::class, 'searchInventory'])->name('search.inventory');
    Route::get('/search/transactions', [SearchController::class, 'searchTransactions'])->name('search.transactions');
});
