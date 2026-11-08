<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Modules\Administration\Controllers\RoleController;
use App\Modules\Administration\Controllers\TaxRateController;
use App\Modules\Administration\Controllers\UserController;
use App\Modules\Catalog\Controllers\ProductAttributeController;
use App\Modules\Catalog\Controllers\ProductCategoryController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Controllers\ProductVariantController;
use App\Modules\Catalog\Controllers\UnitController;
use App\Modules\Inventory\Controllers\InventoryLotController;
use App\Modules\Inventory\Controllers\InventoryMovementController;
use App\Modules\Inventory\Controllers\InventoryStockController;
use App\Modules\Inventory\Controllers\TransferInventoryController;
use App\Modules\Inventory\Controllers\WarehouseController;
use App\Modules\Payments\Controllers\PaymentAllocationController;
use App\Modules\Payments\Controllers\PaymentController;
use App\Modules\Purchasing\Controllers\PurchaseController;
use App\Modules\Purchasing\Controllers\SupplierController;
use App\Modules\Sales\Controllers\CustomerController;
use App\Modules\Sales\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'index'])->name('login.index');
    Route::post('register', [LoginController::class, 'register'])->name('register');
    Route::post('login', [LoginController::class, 'login'])->name('login.login');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('catalog/products', ProductController::class)->names('catalog.products');
    Route::resource('catalog/categories', ProductCategoryController::class)
        ->parameters(['categories' => 'category'])
        ->names('catalog.categories');
    Route::resource('catalog/units', UnitController::class)->names('catalog.units');
    Route::resource('catalog/variants', ProductVariantController::class)->names('catalog.variants');
    Route::resource('catalog/attributes', ProductAttributeController::class)->names('catalog.attributes');

    Route::resource('inventory/warehouses', WarehouseController::class)->names('inventory.warehouses');
    Route::resource('inventory/stock', InventoryStockController::class)
        ->only(['index', 'show'])
        ->names('inventory.stock');
    Route::resource('inventory/movements', InventoryMovementController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->names('inventory.movements');
    Route::resource('inventory/lots', InventoryLotController::class)->names('inventory.lots');
    Route::get('inventory/transfers/create', [TransferInventoryController::class, 'create'])
        ->name('inventory.transfers.create');
    Route::post('inventory/transfers', [TransferInventoryController::class, 'store'])
        ->name('inventory.transfers.store');

    Route::resource('purchasing/suppliers', SupplierController::class)->names('purchasing.suppliers');
    Route::resource('purchasing/purchases', PurchaseController::class)->names('purchasing.purchases');
    Route::post('purchasing/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])
        ->name('purchasing.purchases.receive');
    Route::post('purchasing/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])
        ->name('purchasing.purchases.cancel');

    Route::resource('sales/customers', CustomerController::class)->names('sales.customers');
    Route::resource('sales/sales', SaleController::class)->names('sales.sales');
    Route::post('sales/sales/{sale}/confirm', [SaleController::class, 'confirm'])
        ->name('sales.sales.confirm');
    Route::post('sales/sales/{sale}/cancel', [SaleController::class, 'cancel'])
        ->name('sales.sales.cancel');

    Route::resource('payments/payments', PaymentController::class)->names('payments.payments');
    Route::post('payments/payments/{payment}/allocations', [PaymentAllocationController::class, 'store'])
        ->name('payments.allocations.store');
    Route::delete('payments/payments/{payment}/allocations/{allocation}', [PaymentAllocationController::class, 'destroy'])
        ->name('payments.allocations.destroy');

    Route::resource('admin/roles', RoleController::class)->names('admin.roles');
    Route::resource('admin/users', UserController::class)->names('admin.users');
    Route::resource('admin/tax-rates', TaxRateController::class)
        ->parameters(['tax-rates' => 'taxRate'])
        ->names('admin.tax-rates');
});
