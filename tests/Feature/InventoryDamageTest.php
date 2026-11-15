<?php

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

it('writes off damaged stock through an auditable adjustment', function () {
    $tenant = makeTenant('damage');
    $user = makeTenantUser($tenant);
    app(TenantContext::class)->set($tenant);

    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $stock = InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '10.000',
    ]);

    app(RecordInventoryMovement::class)->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Damage,
        quantity: '-3.000',
        notes: 'Producto roto en bodega',
        user: $user,
    );

    expect($stock->refresh()->quantity_on_hand)->toBe('7.000');
    expect($stock->quantity_quarantined)->toBe('0.000');
});

it('keeps quarantined stock out of available inventory and discards it later', function () {
    $tenant = makeTenant('quarantine');
    $user = makeTenantUser($tenant);
    app(TenantContext::class)->set($tenant);

    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->lotTracked()->create();
    $stock = InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '10.000',
    ]);

    $lot = InventoryLot::factory()->forProduct($product)->forWarehouse($warehouse)->create([
        'quantity_initial' => '10.000',
        'quantity_available' => '10.000',
        'status' => LotStatus::Available,
    ]);

    $movements = app(RecordInventoryMovement::class);

    $movements->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Quarantine,
        quantity: '2.000',
        lot: $lot,
        user: $user,
    );

    expect($stock->refresh()->quantity_on_hand)->toBe('10.000');
    expect($stock->quantity_quarantined)->toBe('2.000');
    expect($stock->quantity_available)->toBe('8.000');
    expect($lot->refresh()->status)->toBe(LotStatus::Quarantine);

    $movements->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Damage,
        quantity: '-2.000',
        lot: $lot,
        notes: 'Descarte de cuarentena',
        user: $user,
    );

    expect($stock->refresh()->quantity_on_hand)->toBe('8.000');
    expect($stock->quantity_quarantined)->toBe('0.000');
    expect($lot->refresh()->quantity_available)->toBe('8.000');
});

it('rejects invalid damage and quarantine quantities', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '2.000',
    ]);

    $movements = app(RecordInventoryMovement::class);

    expect(fn () => $movements->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Damage,
        quantity: '1.000',
    ))->toThrow(ValidationException::class);

    expect(fn () => $movements->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Quarantine,
        quantity: '5.000',
    ))->toThrow(ValidationException::class);
});
