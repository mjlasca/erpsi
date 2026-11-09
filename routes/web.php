<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Modules\Administration\Controllers\RoleController;
use App\Modules\Administration\Controllers\TaxRateController;
use App\Modules\Administration\Controllers\UserController;
use App\Modules\Cash\Controllers\CashReconciliationController;
use App\Modules\Catalog\Controllers\ProductAttributeController;
use App\Modules\Catalog\Controllers\ProductCategoryController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Controllers\ProductImportController;
use App\Modules\Catalog\Controllers\ProductVariantController;
use App\Modules\Catalog\Controllers\UnitController;
use App\Modules\Inventory\Controllers\InventoryLotController;
use App\Modules\Inventory\Controllers\InventoryMovementController;
use App\Modules\Inventory\Controllers\InventoryStockController;
use App\Modules\Inventory\Controllers\TransferInventoryController;
use App\Modules\Inventory\Controllers\WarehouseController;
use App\Modules\Payments\Controllers\PaymentAllocationController;
use App\Modules\Payments\Controllers\PaymentController;
use App\Modules\Purchasing\Controllers\CancelPurchaseController;
use App\Modules\Purchasing\Controllers\PayPurchaseController;
use App\Modules\Purchasing\Controllers\PurchaseController;
use App\Modules\Purchasing\Controllers\PurchaseDetailController;
use App\Modules\Purchasing\Controllers\SupplierController;
use App\Modules\Sales\Controllers\CancelSaleController;
use App\Modules\Sales\Controllers\CustomerController;
use App\Modules\Sales\Controllers\PaySaleController;
use App\Modules\Sales\Controllers\SaleController;
use App\Modules\Sales\Controllers\SaleDetailController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'index'])->name('login.index');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('catalog/products/search', [ProductController::class, 'search'])
        ->name('catalog.products.search');
    Route::resource('catalog/products', ProductController::class)->names('catalog.products');
    Route::prefix('catalog/products/{product}')->scopeBindings()->group(function (): void {
        Route::get('variants', [ProductVariantController::class, 'indexForProduct'])
            ->name('catalog.products.variants.index');
        Route::get('variants/create', [ProductVariantController::class, 'createForProduct'])
            ->name('catalog.products.variants.create');
        Route::post('variants', [ProductVariantController::class, 'storeForProduct'])
            ->name('catalog.products.variants.store');
        Route::get('variants/{variant}', [ProductVariantController::class, 'showForProduct'])
            ->name('catalog.products.variants.show');
        Route::get('variants/{variant}/edit', [ProductVariantController::class, 'editForProduct'])
            ->name('catalog.products.variants.edit');
        Route::match(['put', 'patch'], 'variants/{variant}', [ProductVariantController::class, 'updateForProduct'])
            ->name('catalog.products.variants.update');
        Route::delete('variants/{variant}', [ProductVariantController::class, 'destroyForProduct'])
            ->name('catalog.products.variants.destroy');

        Route::get('attributes', [ProductAttributeController::class, 'indexForProduct'])
            ->name('catalog.products.attributes.index');
        Route::get('attributes/create', [ProductAttributeController::class, 'createForProduct'])
            ->name('catalog.products.attributes.create');
        Route::post('attributes', [ProductAttributeController::class, 'storeForProduct'])
            ->name('catalog.products.attributes.store');
        Route::get('attributes/{attribute}', [ProductAttributeController::class, 'showForProduct'])
            ->name('catalog.products.attributes.show');
        Route::get('attributes/{attribute}/edit', [ProductAttributeController::class, 'editForProduct'])
            ->name('catalog.products.attributes.edit');
        Route::match(['put', 'patch'], 'attributes/{attribute}', [ProductAttributeController::class, 'updateForProduct'])
            ->name('catalog.products.attributes.update');
        Route::delete('attributes/{attribute}', [ProductAttributeController::class, 'destroyForProduct'])
            ->name('catalog.products.attributes.destroy');
    });
    Route::resource('catalog/categories', ProductCategoryController::class)
        ->parameters(['categories' => 'category'])
        ->names('catalog.categories');
    Route::resource('catalog/units', UnitController::class)->names('catalog.units');
    Route::resource('catalog/variants', ProductVariantController::class)->names('catalog.variants');
    Route::resource('catalog/attributes', ProductAttributeController::class)->names('catalog.attributes');

    Route::resource('inventory/warehouses', WarehouseController::class)->names('inventory.warehouses');
    Route::get('inventory/stock/import-products', [ProductImportController::class, 'create'])
        ->name('inventory.stock.import-products.create');
    Route::post('inventory/stock/import-products', [ProductImportController::class, 'store'])
        ->name('inventory.stock.import-products.store');
    Route::get('inventory/stock/import-products/template', [ProductImportController::class, 'template'])
        ->name('inventory.stock.import-products.template');
    Route::resource('inventory/stock', InventoryStockController::class)
        ->only(['index', 'show'])
        ->names('inventory.stock');
    Route::resource('inventory/movements', InventoryMovementController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->names('inventory.movements');
    Route::get('inventory/lots/search', [InventoryLotController::class, 'search'])
        ->name('inventory.lots.search');
    Route::resource('inventory/lots', InventoryLotController::class)->names('inventory.lots');
    Route::get('inventory/transfers/create', [TransferInventoryController::class, 'create'])
        ->name('inventory.transfers.create');
    Route::post('inventory/transfers', [TransferInventoryController::class, 'store'])
        ->name('inventory.transfers.store');

    Route::get('purchasing/suppliers/search', [SupplierController::class, 'search'])
        ->name('purchasing.suppliers.search');
    Route::resource('purchasing/suppliers', SupplierController::class)->names('purchasing.suppliers');
    Route::resource('purchasing/purchases', PurchaseController::class)->names('purchasing.purchases');
    Route::scopeBindings()->group(function (): void {
        Route::get('purchasing/purchases/{purchase}/details', [PurchaseDetailController::class, 'index'])
            ->name('purchasing.purchases.details.index');
        Route::post('purchasing/purchases/{purchase}/details', [PurchaseDetailController::class, 'store'])
            ->name('purchasing.purchases.details.store');
        Route::get('purchasing/purchases/{purchase}/details/{detail}', [PurchaseDetailController::class, 'show'])
            ->name('purchasing.purchases.details.show');
        Route::match(['put', 'patch'], 'purchasing/purchases/{purchase}/details/{detail}', [PurchaseDetailController::class, 'update'])
            ->name('purchasing.purchases.details.update');
        Route::delete('purchasing/purchases/{purchase}/details/{detail}', [PurchaseDetailController::class, 'destroy'])
            ->name('purchasing.purchases.details.destroy');
    });
    Route::post('purchasing/purchases/{purchase}/pay', PayPurchaseController::class)
        ->name('purchasing.purchases.pay');
    Route::post('purchasing/purchases/{purchase}/cancel', CancelPurchaseController::class)
        ->name('purchasing.purchases.cancel');

    Route::get('sales/customers/search', [CustomerController::class, 'search'])
        ->name('sales.customers.search');
    Route::resource('sales/customers', CustomerController::class)->names('sales.customers');
    Route::resource('sales', SaleController::class)->names('sales.sales');
    Route::scopeBindings()->group(function (): void {
        Route::get('sales/{sale}/details', [SaleDetailController::class, 'index'])
            ->name('sales.sales.details.index');
        Route::post('sales/{sale}/details', [SaleDetailController::class, 'store'])
            ->name('sales.sales.details.store');
        Route::get('sales/{sale}/details/{detail}', [SaleDetailController::class, 'show'])
            ->name('sales.sales.details.show');
        Route::match(['put', 'patch'], 'sales/{sale}/details/{detail}', [SaleDetailController::class, 'update'])
            ->name('sales.sales.details.update');
        Route::delete('sales/{sale}/details/{detail}', [SaleDetailController::class, 'destroy'])
            ->name('sales.sales.details.destroy');
    });
    Route::post('sales/{sale}/pay', PaySaleController::class)
        ->name('sales.sales.pay');
    Route::post('sales/{sale}/cancel', CancelSaleController::class)
        ->name('sales.sales.cancel');

    Route::resource('payments', PaymentController::class)->names('payments.payments');
    Route::post('payments/{payment}/allocations', [PaymentAllocationController::class, 'store'])
        ->name('payments.allocations.store');
    Route::delete('payments/{payment}/allocations/{allocation}', [PaymentAllocationController::class, 'destroy'])
        ->name('payments.allocations.destroy');

    Route::get('cash/reconciliations', [CashReconciliationController::class, 'index'])
        ->name('cash.reconciliations.index');
    Route::get('cash/reconciliations/{date}', [CashReconciliationController::class, 'show'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('cash.reconciliations.show');
    Route::put('cash/reconciliations/{date}', [CashReconciliationController::class, 'update'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('cash.reconciliations.update');

    Route::resource('administration/roles', RoleController::class)->names('admin.roles');
    Route::resource('administration/users', UserController::class)->names('admin.users');
    Route::resource('administration/tax-rates', TaxRateController::class)
        ->parameters(['tax-rates' => 'taxRate'])
        ->names('admin.tax-rates');
});
