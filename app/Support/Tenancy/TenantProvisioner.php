<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use App\Modules\Administration\Models\Role;
use App\Modules\Inventory\Models\Warehouse;

class TenantProvisioner
{
    /**
     * Create the baseline roles and operating defaults for a company.
     */
    public function provision(Tenant $tenant): Tenant
    {
        return app(TenantContext::class)->run($tenant, function () use ($tenant): Tenant {
            $roles = [
                'Administrator' => ['*'],
                'Sales' => [
                    'catalog.*.view',
                    'sales.customers.*',
                    'sales.sales.*',
                    'sales.returns.*',
                    'payments.payments.view',
                    'payments.payments.create',
                    'payments.allocations.manage',
                    'cash.reconciliations.view',
                    'cash.reconciliations.create',
                    'cash.reconciliations.update',
                    'cash.sessions.view',
                    'cash.sessions.create',
                    'cash.sessions.close',
                    'cash.sessions.export',
                    'inventory.stock.view',
                ],
                'Inventory Manager' => [
                    'catalog.*.view',
                    'inventory.*',
                    'purchasing.*',
                    'inventory.damages.manage',
                    'sales.sales.view',
                    'inventory.stock.view',
                ],
            ];

            foreach ($roles as $name => $permissions) {
                Role::query()->firstOrCreate(
                    ['name' => $name],
                    ['permissions' => $permissions, 'is_active' => true],
                );
            }

            $warehouse = Warehouse::query()->firstOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => 'Almacén principal',
                    'address' => null,
                    'description' => null,
                    'is_active' => true,
                ],
            );

            $tenant->update(['default_warehouse_id' => $warehouse->id]);

            return $tenant->refresh();
        });
    }
}
