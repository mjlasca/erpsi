<?php

namespace Database\Factories;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAllocation>
 */
class PaymentAllocationFactory extends Factory
{
    protected $model = PaymentAllocation::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'sale_id' => Sale::factory(),
            'purchase_id' => null,
            'amount' => fake()->randomFloat(2, 1, 100),
        ];
    }

    public function forPurchase(): static
    {
        return $this->state(fn (): array => [
            'sale_id' => null,
            'purchase_id' => Purchase::factory(),
        ]);
    }
}
