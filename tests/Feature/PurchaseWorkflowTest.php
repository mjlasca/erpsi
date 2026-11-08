<?php

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Purchasing\Actions\CancelPurchase;
use App\Modules\Purchasing\Actions\ReceivePurchase;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseDetail;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Validation\ValidationException;

it('does not move stock while a purchase is a draft', function () {
    [$purchase] = purchaseWithDetail(quantity: '3.000', unitPrice: '10.00');

    expect($purchase->status)->toBe(PurchaseStatus::Draft);
    expect(InventoryStock::query()->count())->toBe(0);
    expect(InventoryMovement::query()->count())->toBe(0);
});

it('receives a purchase and creates stock and lots', function () {
    $warehouse = Warehouse::factory()->create();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->lotTracked()->create();
    $purchase = Purchase::factory()->create([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);
    PurchaseDetail::factory()->forPurchase($purchase)->create([
        'product_id' => $product->id,
        'quantity' => '4.000',
        'unit_price' => '25.00',
        'tax_rate' => '0.0000',
        'discount' => '0.00',
    ]);

    $received = app(ReceivePurchase::class)->execute($purchase);

    expect($received->status)->toBe(PurchaseStatus::Received);
    expect($received->received_at)->not->toBeNull();

    $stock = InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();

    expect($stock->quantity_on_hand)->toBe('4.000');
    expect(InventoryMovement::query()->where('movement_type', 'PURCHASE')->count())->toBe(1);

    $lot = InventoryLot::query()->where('product_id', $product->id)->firstOrFail();
    expect($lot->quantity_available)->toBe('4.000');
    expect($lot->status)->toBe(LotStatus::Available);
    expect($product->refresh()->cost_price)->toBe('25.00');
});

it('cancels a received non-lot purchase and reverses stock', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $purchase = Purchase::factory()->create(['warehouse_id' => $warehouse->id]);
    PurchaseDetail::factory()->forPurchase($purchase)->create([
        'product_id' => $product->id,
        'quantity' => '5.000',
        'unit_price' => '8.00',
        'tax_rate' => '0.0000',
        'discount' => '0.00',
    ]);

    app(ReceivePurchase::class)->execute($purchase);
    $cancelled = app(CancelPurchase::class)->execute($purchase);

    expect($cancelled->status)->toBe(PurchaseStatus::Cancelled);

    $stock = InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();

    expect($stock->quantity_on_hand)->toBe('0.000');
    expect(InventoryMovement::query()->where('movement_type', 'RETURN')->count())->toBe(1);
});

it('rejects cancelling a received purchase whose lot was consumed', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->lotTracked()->create();
    $purchase = Purchase::factory()->create(['warehouse_id' => $warehouse->id]);
    PurchaseDetail::factory()->forPurchase($purchase)->create([
        'product_id' => $product->id,
        'quantity' => '2.000',
        'unit_price' => '10.00',
        'tax_rate' => '0.0000',
        'discount' => '0.00',
    ]);

    app(ReceivePurchase::class)->execute($purchase);

    $lot = InventoryLot::query()->where('product_id', $product->id)->firstOrFail();
    $lot->update(['quantity_available' => '1.000']);

    expect(fn () => app(CancelPurchase::class)->execute($purchase))
        ->toThrow(ValidationException::class);
});

function purchaseWithDetail(string $quantity, string $unitPrice): array
{
    $purchase = Purchase::factory()->create();
    PurchaseDetail::factory()->forPurchase($purchase)->create([
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'tax_rate' => '0.0000',
        'discount' => '0.00',
    ]);

    return [$purchase];
}
