<?php

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Actions\TransferInventory;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\Decimal;
use Illuminate\Validation\ValidationException;
use LogicException;

it('creates stock from a signed inventory movement', function () {
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    app(RecordInventoryMovement::class)->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Adjustment,
        quantity: '7.500',
    );

    $stock = InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();

    expect($stock->quantity_on_hand)->toBe('7.500');
    expect(InventoryMovement::query()->count())->toBe(1);
});

it('prevents negative stock', function () {
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    expect(fn () => app(RecordInventoryMovement::class)->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Adjustment,
        quantity: '-1.000',
    ))->toThrow(ValidationException::class);
});

it('keeps inventory movements immutable', function () {
    $movement = InventoryMovement::factory()->create();

    expect(fn () => $movement->update(['notes' => 'Cambio']))
        ->toThrow(LogicException::class);

    expect(fn () => $movement->delete())
        ->toThrow(LogicException::class);
});

it('transfers stock between warehouses', function () {
    $product = Product::factory()->create();
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();

    app(RecordInventoryMovement::class)->execute(
        product: $product,
        warehouse: $from,
        movementType: MovementType::Adjustment,
        quantity: '10.000',
    );

    app(TransferInventory::class)->execute($product, $from, $to, '4.000');

    $fromStock = InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $from->id)
        ->firstOrFail();
    $toStock = InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $to->id)
        ->firstOrFail();

    expect($fromStock->quantity_on_hand)->toBe('6.000');
    expect($toStock->quantity_on_hand)->toBe('4.000');
    expect(InventoryMovement::query()->where('movement_type', 'TRANSFER')->count())->toBe(2);
});

it('moves a lot to the destination warehouse when transferring', function () {
    $product = Product::factory()->lotTracked()->create();
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();
    $lot = InventoryLot::factory()->forProduct($product)->forWarehouse($from)->create([
        'quantity_initial' => '5.000',
        'quantity_available' => '5.000',
    ]);

    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $from->id,
        'quantity_on_hand' => '5.000',
    ]);

    app(TransferInventory::class)->execute($product, $from, $to, '5.000', lot: $lot);

    expect($lot->refresh()->warehouse_id)->toBe($to->id);
    expect($lot->quantity_available)->toBe('5.000');
    expect(Decimal::compare((string) $lot->quantity_available, '5.000', 3))->toBe(0);
    expect($lot->status)->toBe(LotStatus::Available);

    expect(InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $to->id)
        ->firstOrFail()
        ->quantity_on_hand)->toBe('5.000');
});
