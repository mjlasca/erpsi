<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'code' => 'WH-'.Str::upper(fake()->unique()->bothify('###??')),
            'name' => fake()->unique()->company().' Warehouse',
            'address' => fake()->address(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function main(): static
    {
        return $this->state(fn (): array => [
            'code' => 'MAIN',
            'name' => 'Almacén principal',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
