<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.Str::upper(fake()->unique()->bothify('######??')),
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'sale_date' => fake()->date(),
            'due_date' => fake()->optional()->date(),
            'subtotal' => 0,
            'discount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'paid_amount' => 0,
            'balance' => 0,
            'status' => SaleStatus::Draft,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'confirmed_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => SaleStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => SaleStatus::Paid,
            'confirmed_at' => now(),
            'total' => 100,
            'paid_amount' => 100,
            'balance' => 0,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => SaleStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (): array => [
            'created_by' => $user->id,
        ]);
    }
}
