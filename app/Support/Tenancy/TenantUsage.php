<?php

namespace App\Support\Tenancy;

use App\Models\Plan;
use App\Models\Tenant;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TenantUsage
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Tenant $tenant): array
    {
        $plan = $tenant->plan;

        $usage = app(TenantContext::class)->run($tenant, function () use ($tenant): array {
            $monthStart = Carbon::now()->startOfMonth()->toDateString();

            return [
                'users' => DB::table('tenant_user')
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->count(),
                'products' => Product::query()->count(),
                'warehouses' => Warehouse::query()->count(),
                'sales_this_month' => Sale::query()
                    ->whereDate('sale_date', '>=', $monthStart)
                    ->where('status', '!=', SaleStatus::Cancelled->value)
                    ->count(),
            ];
        });

        $limits = [
            'users' => $plan?->limit('max_users'),
            'products' => $plan?->limit('max_products'),
            'warehouses' => $plan?->limit('max_warehouses'),
            'sales_this_month' => $plan?->limit('max_monthly_sales'),
        ];

        $percentages = [];

        foreach ($limits as $key => $limit) {
            $percentages[$key] = $limit === null
                ? null
                : (int) min(100, round(($usage[$key] / max($limit, 1)) * 100));
        }

        return [
            ...$usage,
            'plan' => $plan === null ? null : [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'billing_period' => $plan->billing_period,
            ],
            'limits' => $limits,
            'percentages' => $percentages,
        ];
    }

    public function count(Tenant $tenant, string $metric): int
    {
        return (int) ($this->summary($tenant)[$metric] ?? 0);
    }

    public function plan(Tenant $tenant): ?Plan
    {
        return $tenant->plan;
    }
}
