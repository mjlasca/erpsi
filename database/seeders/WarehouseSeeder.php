<?php

namespace Database\Seeders;

use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Almacén principal',
                'address' => null,
                'description' => null,
                'is_active' => true,
            ],
        );
    }
}
