<?php

namespace Database\Seeders;

use App\Modules\Administration\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'Administrator'],
            ['permissions' => ['*'], 'is_active' => true],
        );

        Role::updateOrCreate(
            ['name' => 'Sales'],
            [
                'permissions' => [
                    'catalog.*.view',
                    'sales.customers.*',
                    'sales.sales.*',
                    'payments.payments.view',
                    'payments.payments.create',
                    'payments.allocations.manage',
                    'cash.reconciliations.view',
                    'cash.reconciliations.create',
                    'cash.reconciliations.update',
                    'inventory.stock.view',
                ],
                'is_active' => true,
            ],
        );

        Role::updateOrCreate(
            ['name' => 'Inventory Manager'],
            [
                'permissions' => [
                    'catalog.*.view',
                    'inventory.*',
                    'purchasing.*',
                    'sales.sales.view',
                    'inventory.stock.view',
                ],
                'is_active' => true,
            ],
        );
    }
}
