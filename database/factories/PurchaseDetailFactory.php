<?php

namespace Database\Factories;

use App\Modules\Administration\Models\TaxRate;
use App\Modules\Catalog\Models\Product;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseDetail>
 */
class PurchaseDetailFactory extends Factory
{
    protected $model = PurchaseDetail::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 20);
        $unitPrice = fake()->randomFloat(2, 5, 250);
        $subtotal = round($quantity * $unitPrice, 2);

        return [
            'purchase_id' => Purchase::factory(),
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

    public function forPurchase(Purchase $purchase): static
    {
        return $this->state(fn (): array => [
            'purchase_id' => $purchase->id,
        ]);
    }
}
