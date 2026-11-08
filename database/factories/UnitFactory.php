<?php

namespace Database\Factories;

use App\Modules\Catalog\Enums\UnitType;
use App\Modules\Catalog\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => Str::title($name),
            'abbreviation' => Str::upper(Str::substr($name, 0, 3)).fake()->unique()->numberBetween(10, 99),
            'unit_type' => fake()->randomElement(UnitType::cases()),
            'allows_decimal' => fake()->boolean(),
            'is_active' => true,
        ];
    }

    public function piece(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Unidad',
            'abbreviation' => 'EA',
            'unit_type' => UnitType::Quantity,
            'allows_decimal' => false,
        ]);
    }

    public function squareFoot(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Pie cuadrado',
            'abbreviation' => 'SQFT',
            'unit_type' => UnitType::Area,
            'allows_decimal' => true,
        ]);
    }
}
