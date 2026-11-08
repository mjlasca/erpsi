<?php

namespace Database\Seeders;

use App\Modules\Administration\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::updateOrCreate(
            ['name' => 'Exento', 'jurisdiction' => null],
            ['rate' => 0, 'tax_type' => 'SALES_TAX', 'is_active' => true],
        );
    }
}
