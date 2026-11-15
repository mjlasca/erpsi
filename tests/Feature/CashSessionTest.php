<?php

use App\Modules\Cash\Actions\CloseCashSession;
use App\Modules\Cash\Actions\OpenCashSession;
use App\Modules\Cash\Enums\CashSessionStatus;
use App\Modules\Cash\Models\InventorySnapshot;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

it('captures opening and closing stock snapshots for a cash session', function () {
    $tenant = makeTenant('cash');
    $tenant->update(['timezone' => 'America/Mexico_City']);
    $user = makeTenantUser($tenant);

    app(TenantContext::class)->set($tenant);

    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '10.000',
    ]);

    $session = app(OpenCashSession::class)->execute([
        'warehouse_id' => $warehouse->id,
        'opening_balance' => '100.00',
        'notes' => 'Turno mañana',
    ], $user);

    expect($session->status)->toBe(CashSessionStatus::Open);
    expect($session->snapshots()->where('type', 'OPENING')->count())->toBe(1);
    expect($session->snapshots()->firstOrFail()->lines->firstOrFail()->quantity_on_hand)
        ->toBe('10.000');

    app(RecordInventoryMovement::class)->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Adjustment,
        quantity: '-2.000',
        reference: $session,
        user: $user,
    );

    $closed = app(CloseCashSession::class)->execute($session, '120.00', $user);

    expect($closed->status)->toBe(CashSessionStatus::Closed);
    expect($closed->expected_cash)->toBe('100.00');
    expect($closed->difference)->toBe('20.00');
    expect(InventorySnapshot::query()->where('cash_session_id', $session->id)->count())->toBe(2);

    $closing = $session->snapshots()->where('type', 'CLOSING')->firstOrFail();
    expect($closing->lines->firstOrFail()->quantity_on_hand)->toBe('8.000');
});

it('prevents two open cash sessions for the same user', function () {
    $tenant = makeTenant('cash-two');
    $user = makeTenantUser($tenant);
    app(TenantContext::class)->set($tenant);

    app(OpenCashSession::class)->execute(['opening_balance' => '0'], $user);

    expect(fn () => app(OpenCashSession::class)->execute(['opening_balance' => '0'], $user))
        ->toThrow(ValidationException::class);
});

it('renders the cash session page with stock differences', function () {
    $tenant = makeTenant('cash-page');
    $user = makeTenantUser($tenant);
    app(TenantContext::class)->set($tenant);

    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['name' => 'Caja producto']);
    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '5.000',
    ]);

    $session = app(OpenCashSession::class)->execute([
        'warehouse_id' => $warehouse->id,
        'opening_balance' => '50.00',
    ], $user);

    app(RecordInventoryMovement::class)->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Adjustment,
        quantity: '1.500',
        user: $user,
    );
    app(CloseCashSession::class)->execute($session, '50.00', $user);

    $this->actingAs($user)
        ->get(route('cash.sessions.show', $session))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('session.id', $session->id)
            ->has('stockDifferences', 1)
            ->where('stockDifferences.0.opening', '5.000')
            ->where('stockDifferences.0.closing', '6.500')
            ->where('stockDifferences.0.difference', '1.500'));
});
