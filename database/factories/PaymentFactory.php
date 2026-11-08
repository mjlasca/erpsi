<?php

namespace Database\Factories;

use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Sales\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_type' => PaymentType::CustomerPayment,
            'customer_id' => Customer::factory(),
            'supplier_id' => null,
            'payment_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'reference' => fake()->optional()->bothify('REF-####'),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function supplierPayment(): static
    {
        return $this->state(fn (): array => [
            'payment_type' => PaymentType::SupplierPayment,
            'customer_id' => null,
            'supplier_id' => Supplier::factory(),
        ]);
    }
}
