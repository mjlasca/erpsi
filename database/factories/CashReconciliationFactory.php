<?php

namespace Database\Factories;

use App\Modules\Cash\Models\CashReconciliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashReconciliation>
 */
class CashReconciliationFactory extends Factory
{
    protected $model = CashReconciliation::class;

    public function definition(): array
    {
        return [
            'date' => fake()->unique()->date(),
            'opening_balance' => 0,
            'counted_cash' => null,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
