<?php

namespace Database\Factories;

use App\Modules\Administration\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRate>
 */
class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    public function definition(): array
    {
        return [
            'name' => 'Impuesto '.fake()->unique()->numberBetween(1, 9999),
            'rate' => fake()->randomFloat(4, 0, 20),
            'tax_type' => fake()->randomElement(['SALES_TAX', 'VAT']),
            'jurisdiction' => fake()->state(),
            'is_active' => true,
        ];
    }

    public function exempt(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Exento',
            'rate' => 0,
            'tax_type' => 'SALES_TAX',
            'jurisdiction' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
