<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Administration\Resources\UserResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Resources\InventoryStockResource;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Resources\PurchaseResource;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Resources\SaleResource;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $receivableStatuses = [
            SaleStatus::Confirmed->value,
            SaleStatus::Partial->value,
        ];

        return Inertia::render('DashBoard', [
            'stats' => [
                'active_products' => Product::query()->active()->count(),
                'active_customers' => Customer::query()->active()->count(),
                'active_suppliers' => Supplier::query()->active()->count(),
                'low_stock_items' => InventoryStock::query()->lowStock()->count(),
                'receivable_balance' => (string) Sale::query()->whereIn('status', $receivableStatuses)->sum('balance'),
                'payable_balance' => (string) Purchase::query()->where('status', PurchaseStatus::Received->value)->sum('balance'),
                'sales_this_month' => (string) Sale::query()->where('sale_date', '>=', now()->startOfMonth())->where('status', '!=', SaleStatus::Cancelled->value)->sum('total'),
                'purchases_this_month' => (string) Purchase::query()->where('purchase_date', '>=', now()->startOfMonth())->where('status', '!=', PurchaseStatus::Cancelled->value)->sum('total'),
            ],
            'recentSales' => SaleResource::collection(
                Sale::query()->with('customer')->latest('sale_date')->latest('id')->limit(5)->get(),
            )->resolve(),
            'recentPurchases' => PurchaseResource::collection(
                Purchase::query()->with('supplier')->latest('purchase_date')->latest('id')->limit(5)->get(),
            )->resolve(),
            'lowStockItems' => InventoryStockResource::collection(
                InventoryStock::query()->with(['product', 'variant', 'warehouse'])->lowStock()->orderBy('quantity_on_hand')->limit(5)->get(),
            )->resolve(),
            'user' => (new UserResource(
                User::query()->with('role')->findOrFail(request()->user()->id),
            ))->resolve(),
        ]);
    }
}
