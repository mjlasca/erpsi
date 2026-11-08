<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'unit_id' => null,
            'name' => fake()->randomElement(['Material', 'Color', 'Acabado', 'Ancho', 'Alto']),
            'value' => fake()->word(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (): array => [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);
    }

    public function withUnit(Unit $unit): static
    {
        return $this->state(fn (): array => [
            'unit_id' => $unit->id,
        ]);
    }
}
