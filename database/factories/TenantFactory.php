<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'legal_name' => $name,
            'locale' => 'es',
            'timezone' => 'UTC',
            'currency' => 'MXN',
            'date_format' => 'd/m/Y',
            'week_starts_on' => 1,
            'invoice_prefix' => 'INV',
            'purchase_prefix' => 'COMP',
            'sale_return_prefix' => 'RDEV',
            'purchase_return_prefix' => 'RCOMP',
            'cash_session_prefix' => 'CAJA',
            'is_active' => true,
        ];
    }
}
