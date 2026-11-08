<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Sales\Models\SaleDetail;
use App\Modules\Sales\Models\SaleDetailLotAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleDetailLotAllocation>
 */
class SaleDetailLotAllocationFactory extends Factory
{
    protected $model = SaleDetailLotAllocation::class;

    public function definition(): array
    {
        return [
            'sale_detail_id' => SaleDetail::factory(),
            'inventory_lot_id' => InventoryLot::factory(),
            'quantity' => fake()->randomFloat(3, 0.1, 5),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forSaleDetail(SaleDetail $detail): static
    {
        return $this->state(fn (): array => [
            'sale_detail_id' => $detail->id,
        ]);
    }

    public function forLot(InventoryLot $lot): static
    {
        return $this->state(fn (): array => [
            'inventory_lot_id' => $lot->id,
        ]);
    }
}
