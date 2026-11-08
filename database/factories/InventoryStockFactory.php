<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryStock>
 */
class InventoryStockFactory extends Factory
{
    protected $model = InventoryStock::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'quantity_on_hand' => fake()->randomFloat(3, 0, 100),
            'quantity_reserved' => 0,
            'quantity_minimum' => fake()->randomFloat(3, 0, 10),
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
}
