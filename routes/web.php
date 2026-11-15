<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Platform\PlatformAuditController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\PlatformPlanController;
use App\Http\Controllers\Platform\PlatformSubscriptionController;
use App\Http\Controllers\Platform\PlatformSupportController;
use App\Http\Controllers\Platform\PlatformTenantController;
use App\Http\Controllers\Platform\PlatformUserController;
use App\Http\Controllers\PlatformSettingsController;
use App\Http\Controllers\TenantSwitchController;
use App\Http\Controllers\UserPreferenceController;
use App\Modules\Administration\Controllers\RoleController;
use App\Modules\Administration\Controllers\TaxRateController;
use App\Modules\Administration\Controllers\TenantSettingsController;
use App\Modules\Administration\Controllers\UserController;
use App\Modules\Cash\Controllers\CashReconciliationController;
use App\Modules\Cash\Controllers\CashSessionController;
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
use App\Modules\Purchasing\Controllers\PurchaseReturnController;
use App\Modules\Purchasing\Controllers\SupplierController;
use App\Modules\Sales\Controllers\CancelSaleController;
use App\Modules\Sales\Controllers\CustomerController;
use App\Modules\Sales\Controllers\PaySaleController;
use App\Modules\Sales\Controllers\SaleController;
use App\Modules\Sales\Controllers\SaleDetailController;
use App\Modules\Sales\Controllers\SaleReturnController;
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
    Route::post('tenant/switch/{tenant}', TenantSwitchController::class)->name('tenant.switch');
    Route::get('preferences', [UserPreferenceController::class, 'edit'])->name('preferences.edit');
    Route::put('preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');
    Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
    Route::get('exports/{export}/download', [ExportController::class, 'download'])->name('exports.download');

    Route::get('catalog/products/export', [ProductController::class, 'export'])->name('catalog.products.export');
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
    Route::get('purchasing/purchases/export', [PurchaseController::class, 'export'])
        ->name('purchasing.purchases.export');
    Route::resource('purchasing/purchases', PurchaseController::class)->names('purchasing.purchases');
    Route::get('purchasing/returns', [PurchaseReturnController::class, 'index'])
        ->name('purchasing.returns.index');
    Route::get('purchasing/{purchase}/returns/create', [PurchaseReturnController::class, 'create'])
        ->name('purchasing.returns.create');
    Route::post('purchasing/{purchase}/returns', [PurchaseReturnController::class, 'store'])
        ->name('purchasing.returns.store');
    Route::get('purchasing/returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])
        ->name('purchasing.returns.show');
    Route::post('purchasing/returns/{purchaseReturn}/confirm', [PurchaseReturnController::class, 'confirm'])
        ->name('purchasing.returns.confirm');
    Route::post('purchasing/returns/{purchaseReturn}/cancel', [PurchaseReturnController::class, 'cancel'])
        ->name('purchasing.returns.cancel');
    Route::delete('purchasing/returns/{purchaseReturn}', [PurchaseReturnController::class, 'destroy'])
        ->name('purchasing.returns.destroy');
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
    Route::get('sales/export', [SaleController::class, 'export'])->name('sales.sales.export');
    Route::get('sales/returns', [SaleReturnController::class, 'index'])->name('sales.returns.index');
    Route::get('sales/{sale}/returns/create', [SaleReturnController::class, 'create'])
        ->name('sales.returns.create');
    Route::post('sales/{sale}/returns', [SaleReturnController::class, 'store'])
        ->name('sales.returns.store');
    Route::get('sales/returns/{saleReturn}', [SaleReturnController::class, 'show'])
        ->name('sales.returns.show');
    Route::post('sales/returns/{saleReturn}/confirm', [SaleReturnController::class, 'confirm'])
        ->name('sales.returns.confirm');
    Route::post('sales/returns/{saleReturn}/cancel', [SaleReturnController::class, 'cancel'])
        ->name('sales.returns.cancel');
    Route::delete('sales/returns/{saleReturn}', [SaleReturnController::class, 'destroy'])
        ->name('sales.returns.destroy');
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

    Route::get('cash/sessions', [CashSessionController::class, 'index'])->name('cash.sessions.index');
    Route::get('cash/sessions/create', [CashSessionController::class, 'create'])->name('cash.sessions.create');
    Route::post('cash/sessions', [CashSessionController::class, 'store'])->name('cash.sessions.store');
    Route::get('cash/sessions/{cashSession}', [CashSessionController::class, 'show'])->name('cash.sessions.show');
    Route::post('cash/sessions/{cashSession}/close', [CashSessionController::class, 'close'])->name('cash.sessions.close');
    Route::get('cash/sessions/{cashSession}/export', [CashSessionController::class, 'export'])->name('cash.sessions.export');
    Route::get('cash/sessions/{cashSession}/export-differences', [CashSessionController::class, 'exportDifferences'])->name('cash.sessions.export-differences');

    Route::get('cash/reconciliations', [CashReconciliationController::class, 'index'])
        ->name('cash.reconciliations.index');
    Route::get('cash/reconciliations/{date}', [CashReconciliationController::class, 'show'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('cash.reconciliations.show');
    Route::put('cash/reconciliations/{date}', [CashReconciliationController::class, 'update'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('cash.reconciliations.update');

    Route::get('administration/settings', [TenantSettingsController::class, 'edit'])->name('admin.settings.edit');
    Route::put('administration/settings', [TenantSettingsController::class, 'update'])->name('admin.settings.update');
    Route::resource('administration/roles', RoleController::class)->names('admin.roles');
    Route::resource('administration/users', UserController::class)->names('admin.users');
    Route::resource('administration/tax-rates', TaxRateController::class)
        ->parameters(['tax-rates' => 'taxRate'])
        ->names('admin.tax-rates');

    Route::middleware('platform.admin')
        ->prefix('platform')
        ->name('platform.')
        ->group(function (): void {
            Route::get('/', PlatformDashboardController::class)->name('dashboard');

            Route::get('tenants', [PlatformTenantController::class, 'index'])->name('tenants.index');
            Route::get('tenants/create', [PlatformTenantController::class, 'create'])->name('tenants.create');
            Route::post('tenants', [PlatformTenantController::class, 'store'])->name('tenants.store');
            Route::get('tenants/{tenant}', [PlatformTenantController::class, 'show'])->name('tenants.show');
            Route::post('tenants/{tenant}/suspend', [PlatformTenantController::class, 'suspend'])->name('tenants.suspend');
            Route::post('tenants/{tenant}/reactivate', [PlatformTenantController::class, 'reactivate'])->name('tenants.reactivate');
            Route::post('tenants/{tenant}/impersonate', [PlatformTenantController::class, 'impersonate'])->name('tenants.impersonate');

            Route::resource('plans', PlatformPlanController::class)->except(['show']);
            Route::get('subscriptions', [PlatformSubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::put('subscriptions/{tenant}', [PlatformSubscriptionController::class, 'update'])->name('subscriptions.update');

            Route::get('users', [PlatformUserController::class, 'index'])->name('users.index');
            Route::patch('users/{user}/platform-admin', [PlatformUserController::class, 'togglePlatformAdmin'])->name('users.toggle-platform-admin');

            Route::get('audit', [PlatformAuditController::class, 'index'])->name('audit.index');
            Route::post('support/leave', [PlatformSupportController::class, 'leave'])->name('support.leave');

            Route::get('settings', [PlatformSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
        });
});
