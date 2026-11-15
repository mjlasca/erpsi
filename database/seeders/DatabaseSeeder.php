<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TenantSeeder::class);

        $tenant = Tenant::query()->where('slug', 'default')->firstOrFail();
        app(TenantContext::class)->set($tenant);
        app()->setLocale($tenant->locale);

        $this->call([
            PlanSeeder::class,
            RoleSeeder::class,
            DefaultUserSeeder::class,
            UnitSeeder::class,
            TaxRateSeeder::class,
            WarehouseSeeder::class,
            ProductSeeder::class,
        ]);

        $warehouse = Warehouse::query()
            ->where('code', 'MAIN')
            ->first();

        if ($warehouse !== null) {
            $tenant->update(['default_warehouse_id' => $warehouse->id]);
        }
    }
}
