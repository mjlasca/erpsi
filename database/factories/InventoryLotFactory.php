<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InventoryLot>
 */
class InventoryLotFactory extends Factory
{
    protected $model = InventoryLot::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 50);

        return [
            'product_id' => Product::factory()->lotTracked(),
            'product_variant_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'purchase_detail_id' => null,
            'parent_lot_id' => null,
            'code' => 'LOT-'.Str::upper(fake()->unique()->bothify('######??')),
            'lot_number' => fake()->optional()->bothify('LOT-####'),
            'serial_number' => fake()->optional()->bothify('SN-########'),
            'quantity_initial' => $quantity,
            'quantity_available' => $quantity,
            'unit_cost' => fake()->randomFloat(4, 1, 500),
            'status' => LotStatus::Available,
            'received_at' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
            'metadata' => null,
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (): array => [
            'product_id' => $product->id,
        ]);
    }

    public function forWarehouse(Warehouse $warehouse): static
    {
        return $this->state(fn (): array => [
            'warehouse_id' => $warehouse->id,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (): array => [
            'quantity_available' => 0,
            'status' => LotStatus::Sold,
        ]);
    }
}
