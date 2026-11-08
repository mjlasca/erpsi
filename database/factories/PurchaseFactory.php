<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'purchase_number' => 'PUR-'.Str::upper(fake()->unique()->bothify('######??')),
            'supplier_id' => Supplier::factory(),
            'supplier_invoice' => fake()->optional()->bothify('INV-####'),
            'warehouse_id' => Warehouse::factory(),
            'purchase_date' => fake()->date(),
            'subtotal' => 0,
            'discount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'paid_amount' => 0,
            'balance' => 0,
            'status' => PurchaseStatus::Draft,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'received_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function received(): static
    {
        return $this->state(fn (): array => [
            'status' => PurchaseStatus::Received,
            'received_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => PurchaseStatus::Cancelled,
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
