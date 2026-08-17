<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\MerchantProductController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('token-login', [AuthController::class, 'tokenLogin']);
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
});

// MANAGER: Master data, administrasi pengguna & monitoring
Route::middleware(['auth:sanctum', 'role:manager'])->group(function () {

    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);

    Route::post('users/roles', [UserRoleController::class, 'assignRole']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);

    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('merchants', MerchantController::class);

    // Admin: menghapus/mengeluarkan produk dari gudang
    Route::delete('warehouses/{warehouse}/products/{product}', [WarehouseProductController::class, 'detach']);

    // Monitoring: seluruh transaksi & stock out
    Route::get('transactions', [TransactionController::class, 'index']);

});

// KEEPER: Operasional stok gudang, distribusi ke outlet sendiri, transaksi & stock out outlet sendiri
Route::middleware(['auth:sanctum', 'role:keeper'])->group(function () {

    // Kelola stok gudang (menambah/mengurangi)
    Route::post('warehouses/{warehouse}/products', [WarehouseProductController::class, 'attach']);
    Route::put('warehouses/{warehouse}/products/{product}', [WarehouseProductController::class, 'update']);

    // Distribusi stok ke outlet (kepemilikan divalidasi di controller)
    Route::post('merchants/{merchant}/products', [MerchantProductController::class, 'store']);
    Route::put('merchants/{merchant}/products/{product}', [MerchantProductController::class, 'update']);
    Route::delete('merchants/{merchant}/products/{product}', [MerchantProductController::class, 'destroy']);

    // Transaksi penjualan outlet sendiri
    Route::post('transactions', [TransactionController::class, 'store']);

    // Stock out outlet sendiri
    Route::post('stock-outs', [StockOutController::class, 'store']);

});

// MANAGER & KEEPER: Data yang boleh dilihat bersama (scoping per role di controller)
Route::middleware(['auth:sanctum', 'role:manager|keeper'])->group(function () {

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::get('warehouses', [WarehouseController::class, 'index']);
    Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show']);

    // Lihat produk & stok gudang
    Route::get('warehouses/{warehouse}/products', [WarehouseProductController::class, 'show']);

    Route::get('transactions/{transaction}', [TransactionController::class, 'show']);

    Route::get('my-merchant', [MerchantController::class, 'getMyMerchantProfile']);
    Route::get('/my-merchant/transactions', [TransactionController::class, 'getTransactionsByMerchant']);

    // Stock out: manager melihat semua, keeper hanya outlet sendiri (di-scope di controller)
    Route::get('stock-outs', [StockOutController::class, 'index']);

});
