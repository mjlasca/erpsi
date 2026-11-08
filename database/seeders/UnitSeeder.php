<?php

namespace Database\Seeders;

use App\Modules\Catalog\Enums\UnitType;
use App\Modules\Catalog\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Unidad', 'abbreviation' => 'EA', 'unit_type' => UnitType::Quantity, 'allows_decimal' => false],
            ['name' => 'Pie cuadrado', 'abbreviation' => 'SQFT', 'unit_type' => UnitType::Area, 'allows_decimal' => true],
            ['name' => 'Pie lineal', 'abbreviation' => 'LF', 'unit_type' => UnitType::Length, 'allows_decimal' => true],
            ['name' => 'Pie', 'abbreviation' => 'FT', 'unit_type' => UnitType::Length, 'allows_decimal' => true],
            ['name' => 'Pieza', 'abbreviation' => 'PCS', 'unit_type' => UnitType::Quantity, 'allows_decimal' => false],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['abbreviation' => $unit['abbreviation']],
                [...$unit, 'is_active' => true],
            );
        }
    }
}
