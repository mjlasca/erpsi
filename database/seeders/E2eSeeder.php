<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Administration\Models\Role;
use App\Modules\Catalog\Enums\ProductType;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Unit;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Sales\Models\Customer;
use Illuminate\Database\Seeder;

class E2eSeeder extends Seeder
{
    public function run(): void
    {
        $salesRole = Role::query()->where('name', 'Sales')->firstOrFail();
        $inventoryRole = Role::query()->where('name', 'Inventory Manager')->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => 'sales@erpsi.test'],
            [
                'name' => 'Vendedor E2E',
                'password' => 'password',
                'role_id' => $salesRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'inventory@erpsi.test'],
            [
                'name' => 'Inventario E2E',
                'password' => 'password',
                'role_id' => $inventoryRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        Supplier::query()->updateOrCreate(
            ['name' => 'Proveedor E2E'],
            [
                'company_name' => 'Proveedor E2E SA',
                'tax_id' => 'E2E-SUPPLIER',
                'email' => 'proveedor@erpsi.test',
                'is_active' => true,
            ],
        );

        Supplier::query()->updateOrCreate(
            ['name' => 'Proveedor Borrar'],
            [
                'company_name' => 'Proveedor Borrar SA',
                'tax_id' => 'E2E-DELETE',
                'email' => 'borrar@erpsi.test',
                'is_active' => true,
            ],
        );

        Customer::query()->updateOrCreate(
            ['email' => 'cliente@erpsi.test'],
            [
                'first_name' => 'Cliente',
                'last_name' => 'E2E',
                'company_name' => 'Cliente E2E SA',
                'is_active' => true,
            ],
        );

        $unit = Unit::query()->where('abbreviation', 'EA')->firstOrFail();

        Product::query()->updateOrCreate(
            ['sku' => 'E2E-PROD-001'],
            [
                'name' => 'Producto E2E',
                'category_id' => null,
                'unit_id' => $unit->id,
                'product_type' => ProductType::Goods,
                'cost_price' => '10.00',
                'sale_price' => '20.00',
                'min_stock' => '0.000',
                'is_inventory' => true,
                'track_lots' => false,
                'is_active' => true,
            ],
        );
    }
}
