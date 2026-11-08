<?php

namespace Database\Factories;

use App\Modules\Catalog\Enums\ProductType;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductCategory;
use App\Modules\Catalog\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 10, 500);
        $sale = $cost * fake()->randomFloat(2, 1.1, 1.8);

        return [
            'sku' => 'SKU-'.Str::upper(fake()->unique()->bothify('####??')),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'category_id' => ProductCategory::factory(),
            'unit_id' => Unit::factory(),
            'product_type' => ProductType::Goods,
            'brand' => fake()->optional()->company(),
            'barcode' => fake()->unique()->ean13(),
            'cost_price' => $cost,
            'sale_price' => round($sale, 2),
            'min_stock' => fake()->randomFloat(3, 0, 10),
            'is_inventory' => true,
            'track_lots' => false,
            'is_active' => true,
        ];
    }

    public function service(): static
    {
        return $this->state(fn (): array => [
            'product_type' => ProductType::Service,
            'is_inventory' => false,
            'track_lots' => false,
            'barcode' => null,
        ]);
    }

    public function lotTracked(): static
    {
        return $this->state(fn (): array => [
            'track_lots' => true,
            'is_inventory' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function forCategory(ProductCategory $category): static
    {
        return $this->state(fn (): array => [
            'category_id' => $category->id,
        ]);
    }

    public function forUnit(Unit $unit): static
    {
        return $this->state(fn (): array => [
            'unit_id' => $unit->id,
        ]);
    }
}
