<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

class TenantLimitChecker
{
    public function __construct(protected TenantUsage $usage) {}

    public function assertUsers(Tenant $tenant): void
    {
        $this->assert($tenant, 'max_users', 'users', 'usuarios');
    }

    public function assertProducts(Tenant $tenant, int $additional = 1): void
    {
        $this->assert($tenant, 'max_products', 'products', 'productos', $additional);
    }

    public function assertWarehouses(Tenant $tenant): void
    {
        $this->assert($tenant, 'max_warehouses', 'warehouses', 'almacenes');
    }

    public function assertMonthlySales(Tenant $tenant): void
    {
        $this->assert($tenant, 'max_monthly_sales', 'sales_this_month', 'ventas este mes');
    }

    protected function assert(Tenant $tenant, string $limitKey, string $usageKey, string $label, int $additional = 1): void
    {
        $plan = $tenant->plan;

        if ($plan === null) {
            return;
        }

        $limit = $plan->limit($limitKey);

        if ($limit === null) {
            return;
        }

        $used = $this->usage->summary($tenant)[$usageKey] ?? 0;

        if ($used + $additional > $limit) {
            throw ValidationException::withMessages([
                'plan' => "El plan {$plan->name} alcanzó el límite de {$limit} {$label}.",
            ]);
        }
    }
}
