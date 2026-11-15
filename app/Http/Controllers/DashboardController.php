<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Administration\Resources\UserResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Resources\InventoryStockResource;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Resources\PurchaseResource;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Resources\SaleResource;
use App\Support\Decimal;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TenantContext $context): Response
    {
        $timezone = $context->timezone();
        $today = Carbon::now($timezone)->startOfDay();
        $months = in_array($request->integer('months'), [6, 12], true) ? $request->integer('months') : 6;
        $from = $today->copy()->subMonths($months - 1)->startOfMonth();
        $to = $today->copy()->endOfMonth();
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $receivableStatuses = [
            SaleStatus::Confirmed->value,
            SaleStatus::Partial->value,
        ];

        $recentSales = Sale::query()
            ->with('customer')
            ->latest('sale_date')
            ->latest('id')
            ->limit(5)
            ->get();

        return Inertia::render('DashBoard/Index', [
            'stats' => [
                'active_products' => Product::query()->active()->count(),
                'active_customers' => Customer::query()->active()->count(),
                'active_suppliers' => Supplier::query()->active()->count(),
                'low_stock_items' => InventoryStock::query()->lowStock()->count(),
                'receivable_balance' => (string) Sale::query()->whereIn('status', $receivableStatuses)->sum('balance'),
                'payable_balance' => (string) Purchase::query()->where('status', PurchaseStatus::Received->value)->sum('balance'),
                'sales_this_month' => (string) Sale::query()
                    ->where('sale_date', '>=', $today->copy()->startOfMonth()->toDateString())
                    ->where('status', '!=', SaleStatus::Cancelled->value)
                    ->sum('total'),
                'purchases_this_month' => (string) Purchase::query()
                    ->where('purchase_date', '>=', $today->copy()->startOfMonth()->toDateString())
                    ->where('status', '!=', PurchaseStatus::Cancelled->value)
                    ->sum('total'),
            ],
            'recentSales' => SaleResource::collection($recentSales)->resolve(),
            'recentPurchases' => PurchaseResource::collection(
                Purchase::query()->with('supplier')->latest('purchase_date')->latest('id')->limit(5)->get(),
            )->resolve(),
            'lowStockItems' => InventoryStockResource::collection(
                InventoryStock::query()->with(['product', 'variant', 'warehouse'])->lowStock()->orderBy('quantity_on_hand')->limit(5)->get(),
            )->resolve(),
            'user' => (new UserResource(
                User::query()->with('role')->findOrFail($request->user()->id),
            ))->resolve(),
            'charts' => [
                'salesPurchases' => $this->salesPurchasesSeries($from, $to, $months, $warehouseId),
                'cashFlow' => $this->cashFlowSeries($today),
            ],
            'filters' => [
                'months' => $months,
                'warehouse_id' => $warehouseId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * @return array<int, array{label: string, sales: string, purchases: string}>
     */
    protected function salesPurchasesSeries(Carbon $from, Carbon $to, int $months, ?int $warehouseId): array
    {
        Carbon::setLocale(app()->getLocale());
        $sales = Sale::query()
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->where('status', '!=', SaleStatus::Cancelled->value)
            ->when($warehouseId, fn ($query, int $id) => $query->where('warehouse_id', $id))
            ->get(['sale_date', 'total'])
            ->groupBy(fn (Sale $sale): string => Carbon::parse((string) $sale->sale_date)->format('Y-m'))
            ->map(fn ($rows): string => Decimal::sum($rows->pluck('total'), 2));

        $purchases = Purchase::query()
            ->whereDate('purchase_date', '>=', $from->toDateString())
            ->whereDate('purchase_date', '<=', $to->toDateString())
            ->where('status', '!=', PurchaseStatus::Cancelled->value)
            ->when($warehouseId, fn ($query, int $id) => $query->where('warehouse_id', $id))
            ->get(['purchase_date', 'total'])
            ->groupBy(fn (Purchase $purchase): string => Carbon::parse((string) $purchase->purchase_date)->format('Y-m'))
            ->map(fn ($rows): string => Decimal::sum($rows->pluck('total'), 2));

        $series = [];

        for ($index = $months - 1; $index >= 0; $index--) {
            $month = Carbon::parse($to->toDateString())->subMonths($index);
            $key = $month->format('Y-m');
            $series[] = [
                'label' => $month->translatedFormat('M Y'),
                'sales' => $sales->get($key, '0.00'),
                'purchases' => $purchases->get($key, '0.00'),
            ];
        }

        return $series;
    }

    /**
     * @return array<int, array{label: string, cash_in: string, cash_out: string}>
     */
    protected function cashFlowSeries(Carbon $today): array
    {
        Carbon::setLocale(app()->getLocale());
        $from = $today->copy()->subDays(13);
        $payments = Payment::query()
            ->where('payment_method', PaymentMethod::Cash)
            ->whereDate('payment_date', '>=', $from->toDateString())
            ->whereDate('payment_date', '<=', $today->toDateString())
            ->get(['payment_date', 'payment_type', 'amount'])
            ->groupBy(fn (Payment $payment): string => Carbon::parse((string) $payment->payment_date)->toDateString());

        $series = [];

        for ($index = 13; $index >= 0; $index--) {
            $day = Carbon::parse($today->toDateString())->subDays($index);
            $rows = $payments->get($day->toDateString(), collect());
            $cashIn = Decimal::sum(
                $rows->whereIn('payment_type', [PaymentType::CustomerPayment, PaymentType::SupplierRefund])->pluck('amount'),
                2,
            );
            $cashOut = Decimal::sum(
                $rows->whereIn('payment_type', [PaymentType::SupplierPayment, PaymentType::CustomerRefund])->pluck('amount'),
                2,
            );

            $series[] = [
                'label' => $day->translatedFormat('d M'),
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
            ];
        }

        return $series;
    }
}
