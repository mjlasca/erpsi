<?php

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductVariant;

it('creates a variant through the nested product route', function () {
    $user = User::factory()->administrator()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->post(route('catalog.products.variants.store', $product), [
            'sku' => 'VAR-NESTED-1',
            'name' => 'Variante anidada',
            'cost_price' => '10.00',
            'sale_price' => '15.00',
            'is_active' => true,
        ])
        ->assertRedirect(route('catalog.products.variants.edit', [
            'product' => $product,
            'variant' => ProductVariant::query()->where('sku', 'VAR-NESTED-1')->firstOrFail(),
        ]));

    expect($product->variants()->where('sku', 'VAR-NESTED-1')->exists())->toBeTrue();
});

it('does not expose a variant from another product through the nested route', function () {
    $user = User::factory()->administrator()->create();
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();
    $variant = ProductVariant::factory()->forProduct($otherProduct)->create();

    $this->actingAs($user)
        ->get(route('catalog.products.variants.show', [
            'product' => $product,
            'variant' => $variant,
        ]))
        ->assertNotFound();
});

it('creates an attribute through the nested product route', function () {
    $user = User::factory()->administrator()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->post(route('catalog.products.attributes.store', $product), [
            'name' => 'Material',
            'value' => 'Roble',
            'sort_order' => 1,
        ])
        ->assertRedirect();

    expect($product->attributes()->where('name', 'Material')->exists())->toBeTrue();
});

it('rejects an attribute variant that belongs to another product', function () {
    $user = User::factory()->administrator()->create();
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();
    $variant = ProductVariant::factory()->forProduct($otherProduct)->create();

    $this->actingAs($user)
        ->post(route('catalog.products.attributes.store', $product), [
            'product_variant_id' => $variant->id,
            'name' => 'Color',
            'value' => 'Negro',
        ])
        ->assertSessionHasErrors('product_variant_id');

    expect(ProductAttribute::query()->count())->toBe(0);
});
