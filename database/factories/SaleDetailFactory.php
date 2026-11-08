<?php

namespace Database\Factories;

use App\Modules\Administration\Models\TaxRate;
use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleDetail>
 */
class SaleDetailFactory extends Factory
{
    protected $model = SaleDetail::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 20);
        $unitPrice = fake()->randomFloat(2, 10, 400);
        $subtotal = round($quantity * $unitPrice, 2);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => 0,
            'tax_rate_id' => TaxRate::factory(),
            'tax_rate' => 0,
            'tax_amount' => 0,
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ];
    }

    public function forSale(Sale $sale): static
    {
        return $this->state(fn (): array => [
            'sale_id' => $sale->id,
        ]);
    }
}
