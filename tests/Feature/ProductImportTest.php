<?php

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductCategory;
use App\Modules\Catalog\Models\Unit;
use Illuminate\Http\UploadedFile;

function productCsv(string $row): string
{
    return "sku,name,description,category_name,unit_abbreviation,product_type,brand,barcode,cost_price,sale_price,min_stock,is_inventory,track_lots,is_active\n{$row}\n";
}

it('downloads the product import template', function () {
    $user = User::factory()->administrator()->create();

    $response = $this->actingAs($user)
        ->get(route('inventory.stock.import-products.template'))
        ->assertOk();

    $content = $response->streamedContent();

    expect($content)->toContain('sku,name,description');
    expect($content)->toContain('unit_abbreviation');
    expect($content)->toContain('product_type');
});

it('imports products from a csv file', function () {
    $user = User::factory()->administrator()->create();
    $unit = Unit::factory()->create(['abbreviation' => 'IMP']);
    $category = ProductCategory::factory()->create(['name' => 'Importados']);

    $csv = productCsv(
        'IMP-001,Producto importado,Descripción,Importados,IMP,GOODS,Marca,,10.00,20.00,0,1,0,1',
    );

    $this->actingAs($user)
        ->post(route('inventory.stock.import-products.store'), [
            'file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])
        ->assertRedirect(route('catalog.products.index'))
        ->assertSessionHas('success', '1 productos importados correctamente.');

    $this->assertDatabaseHas('products', [
        'sku' => 'IMP-001',
        'name' => 'Producto importado',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'product_type' => 'GOODS',
        'is_inventory' => 1,
        'track_lots' => 0,
    ]);
});

it('rejects rows with missing relationships', function () {
    $user = User::factory()->administrator()->create();

    $csv = productCsv(
        'IMP-002,Producto inválido,,,NO-EXISTE,GOODS,,,10.00,20.00,0,1,0,1',
    );

    $this->actingAs($user)
        ->post(route('inventory.stock.import-products.store'), [
            'file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])
        ->assertSessionHasErrors('file');

    expect(Product::query()->where('sku', 'IMP-002')->exists())->toBeFalse();
});

it('rejects duplicate skus and invalid lot configuration', function () {
    $user = User::factory()->administrator()->create();
    Unit::factory()->create(['abbreviation' => 'DUP']);
    Product::factory()->create(['sku' => 'DUP-001']);

    $csv = productCsv(
        'DUP-001,Duplicado,,,DUP,GOODS,,,10.00,20.00,0,1,0,1',
    );

    $this->actingAs($user)
        ->post(route('inventory.stock.import-products.store'), [
            'file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])
        ->assertSessionHasErrors('file');

    $csv = productCsv(
        'DUP-002,Sin inventario,,,DUP,GOODS,,,10.00,20.00,0,0,1,1',
    );

    $this->actingAs($user)
        ->post(route('inventory.stock.import-products.store'), [
            'file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])
        ->assertSessionHasErrors('file');
});
