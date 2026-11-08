<?php

use App\Modules\Administration\Models\TaxRate;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Sales\Actions\CancelSale;
use App\Modules\Sales\Actions\ConfirmSale;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;
use Illuminate\Validation\ValidationException;

it('keeps inventory untouched while a sale is a draft', function () {
    [$sale, $product, $warehouse] = saleWithStock(quantity: '2.000', stock: '10.000');

    expect($sale->status)->toBe(SaleStatus::Draft);
    expect(stockFor($product, $warehouse))->toBe('10.000');
});

it('confirms a sale and discounts stock', function () {
    [$sale, $product, $warehouse] = saleWithStock(quantity: '3.000', stock: '10.000');

    $confirmed = app(ConfirmSale::class)->execute($sale);

    expect($confirmed->status)->toBe(SaleStatus::Confirmed);
    expect($confirmed->confirmed_at)->not->toBeNull();
    expect(stockFor($product, $warehouse))->toBe('7.000');
});

it('confirms a lot-tracked sale using FIFO allocation', function () {
    $warehouse = Warehouse::factory()->create();
    $customer = Customer::factory()->create();
    $product = Product::factory()->lotTracked()->create();

    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '5.000',
    ]);

    $firstLot = InventoryLot::factory()->forProduct($product)->forWarehouse($warehouse)->create([
        'quantity_initial' => '2.000',
        'quantity_available' => '2.000',
        'received_at' => now()->subDay(),
    ]);
    $secondLot = InventoryLot::factory()->forProduct($product)->forWarehouse($warehouse)->create([
        'quantity_initial' => '3.000',
        'quantity_available' => '3.000',
        'received_at' => now(),
    ]);

    $sale = Sale::factory()->create([
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
    ]);
    $detail = SaleDetail::factory()->forSale($sale)->create([
        'product_id' => $product->id,
        'quantity' => '4.000',
        'unit_price' => '10.00',
        'discount' => '0.00',
        'tax_rate' => '0.0000',
        'tax_rate_id' => TaxRate::factory()->exempt(),
    ]);

    app(ConfirmSale::class)->execute($sale);

    expect($firstLot->refresh()->quantity_available)->toBe('0.000');
    expect($firstLot->status)->toBe(LotStatus::Sold);
    expect($secondLot->refresh()->quantity_available)->toBe('1.000');
    expect($secondLot->status)->toBe(LotStatus::Partial);

    $allocations = $detail->lotAllocations()->orderBy('inventory_lot_id')->get();
    expect($allocations)->toHaveCount(2);
    expect($allocations->sum(fn ($allocation) => (float) $allocation->quantity))->toBe(4.0);
    expect(stockFor($product, $warehouse))->toBe('1.000');
});

it('rejects sales without enough stock', function () {
    [$sale] = saleWithStock(quantity: '5.000', stock: '2.000');

    expect(fn () => app(ConfirmSale::class)->execute($sale))
        ->toThrow(ValidationException::class);
});

it('cancels a confirmed sale and restores stock', function () {
    [$sale, $product, $warehouse] = saleWithStock(quantity: '4.000', stock: '10.000');

    app(ConfirmSale::class)->execute($sale);
    $cancelled = app(CancelSale::class)->execute($sale);

    expect($cancelled->status)->toBe(SaleStatus::Cancelled);
    expect(stockFor($product, $warehouse))->toBe('10.000');
});

function saleWithStock(string $quantity, string $stock): array
{
    $warehouse = Warehouse::factory()->create();
    $customer = Customer::factory()->create();
    $product = Product::factory()->create();

    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => $stock,
    ]);

    $sale = Sale::factory()->create([
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
    ]);

    SaleDetail::factory()->forSale($sale)->create([
        'product_id' => $product->id,
        'quantity' => $quantity,
        'unit_price' => '10.00',
        'discount' => '0.00',
        'tax_rate' => '0.0000',
        'tax_rate_id' => TaxRate::factory()->exempt(),
    ]);

    return [$sale, $product, $warehouse];
}

function stockFor(Product $product, Warehouse $warehouse): string
{
    return InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail()
        ->quantity_on_hand;
}
