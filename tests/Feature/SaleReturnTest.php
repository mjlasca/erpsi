<?php

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Sales\Actions\CancelSaleReturn;
use App\Modules\Sales\Actions\ConfirmSale;
use App\Modules\Sales\Actions\ConfirmSaleReturn;
use App\Modules\Sales\Enums\ReturnDisposition;
use App\Modules\Sales\Enums\ReturnStatus;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;
use App\Modules\Sales\Models\SaleReturn;
use App\Modules\Sales\Models\SaleReturnDetail;
use Illuminate\Validation\ValidationException;

function confirmedSaleWithStock(string $quantity = '4.000', string $stock = '10.000'): array
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

    $detail = SaleDetail::factory()->forSale($sale)->create([
        'product_id' => $product->id,
        'quantity' => $quantity,
        'unit_price' => '10.00',
        'discount' => '0.00',
        'tax_rate' => '0.0000',
        'tax_rate_id' => null,
    ]);

    app(ConfirmSale::class)->execute($sale);

    return [$sale->refresh(), $detail->refresh(), $product, $warehouse];
}

function makeSaleReturn(Sale $sale, SaleDetail $detail, string $quantity, ReturnDisposition $disposition): SaleReturn
{
    $return = SaleReturn::query()->create([
        'sale_id' => $sale->id,
        'return_number' => 'RDEV-TEST-'.uniqid(),
        'return_date' => now()->toDateString(),
        'status' => ReturnStatus::Draft,
    ]);

    SaleReturnDetail::query()->create([
        'sale_return_id' => $return->id,
        'sale_detail_id' => $detail->id,
        'product_id' => $detail->product_id,
        'product_variant_id' => $detail->product_variant_id,
        'quantity' => $quantity,
        'unit_price' => $detail->unit_price,
        'discount' => '0.00',
        'tax_rate' => '0.0000',
        'tax_amount' => '0.00',
        'subtotal' => '0.00',
        'total' => '0.00',
        'disposition' => $disposition,
    ]);

    return $return;
}

it('confirms a partial sale return and restores sellable stock', function () {
    [$sale, $detail, $product, $warehouse] = confirmedSaleWithStock();
    $return = makeSaleReturn($sale, $detail, '2.000', ReturnDisposition::Restock);

    $confirmed = app(ConfirmSaleReturn::class)->execute($return);

    expect($confirmed->status)->toBe(ReturnStatus::Confirmed);
    expect($confirmed->total)->toBe('20.00');
    expect($sale->refresh()->returned_amount)->toBe('20.00');
    expect($sale->balance)->toBe('20.00');
    expect($sale->status)->toBe(SaleStatus::Confirmed);

    $stock = InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();

    expect($stock->quantity_on_hand)->toBe('8.000');
    expect($stock->quantity_quarantined)->toBe('0.000');
});

it('does not make quarantined returned stock available for sale', function () {
    [$sale, $detail, $product, $warehouse] = confirmedSaleWithStock();
    $return = makeSaleReturn($sale, $detail, '1.000', ReturnDisposition::Quarantine);

    app(ConfirmSaleReturn::class)->execute($return);

    $stock = InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();

    expect($stock->quantity_on_hand)->toBe('7.000');
    expect($stock->quantity_quarantined)->toBe('1.000');
    expect($stock->quantity_available)->toBe('6.000');
});

it('rejects returns above the pending quantity', function () {
    [$sale, $detail] = confirmedSaleWithStock();
    $first = makeSaleReturn($sale, $detail, '2.000', ReturnDisposition::Restock);
    app(ConfirmSaleReturn::class)->execute($first);

    $second = makeSaleReturn($sale, $detail, '3.000', ReturnDisposition::Restock);

    expect(fn () => app(ConfirmSaleReturn::class)->execute($second))
        ->toThrow(ValidationException::class);
});

it('cancels a confirmed return and reverses the stock movement', function () {
    [$sale, $detail, $product, $warehouse] = confirmedSaleWithStock();
    $return = makeSaleReturn($sale, $detail, '2.000', ReturnDisposition::Restock);
    app(ConfirmSaleReturn::class)->execute($return);

    $cancelled = app(CancelSaleReturn::class)
        ->execute($return->refresh());

    $stock = InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();

    expect($cancelled->status)->toBe(ReturnStatus::Cancelled);
    expect($sale->refresh()->returned_amount)->toBe('0.00');
    expect($stock->quantity_on_hand)->toBe('6.000');
});
