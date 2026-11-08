<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'inventory_lot_id' => null,
            'movement_type' => MovementType::Adjustment,
            'quantity' => fake()->randomFloat(3, -10, 10),
            'unit_cost' => fake()->randomFloat(4, 1, 500),
            'reference_type' => null,
            'reference_id' => null,
            'movement_date' => now(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function purchase(): static
    {
        return $this->state(fn (): array => [
            'movement_type' => MovementType::Purchase,
            'quantity' => fake()->randomFloat(3, 1, 20),
        ]);
    }

    public function sale(): static
    {
        return $this->state(fn (): array => [
            'movement_type' => MovementType::Sale,
            'quantity' => fake()->randomFloat(3, -20, -1),
        ]);
    }

    public function withLot(InventoryLot $lot): static
    {
        return $this->state(fn (): array => [
            'inventory_lot_id' => $lot->id,
            'product_id' => $lot->product_id,
            'product_variant_id' => $lot->product_variant_id,
            'warehouse_id' => $lot->warehouse_id,
        ]);
    }
}
