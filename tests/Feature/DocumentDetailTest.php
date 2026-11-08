<?php

use App\Models\User;
use App\Modules\Administration\Models\TaxRate;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseDetail;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;

it('manages purchase details through nested routes', function () {
    $user = User::factory()->administrator()->create();
    $purchase = Purchase::factory()->create();
    $product = Product::factory()->create();
    $taxRate = TaxRate::factory()->exempt()->create();

    $this->actingAs($user)
        ->post(route('purchasing.purchases.details.store', $purchase), [
            'product_id' => $product->id,
            'quantity' => '2.000',
            'unit_price' => '10.00',
            'discount' => '0.00',
            'tax_rate_id' => $taxRate->id,
        ])
        ->assertRedirect(route('purchasing.purchases.show', $purchase));

    $detail = $purchase->details()->firstOrFail();
    expect($purchase->refresh()->total)->toBe('20.00');

    $this->actingAs($user)
        ->put(route('purchasing.purchases.details.update', [
            'purchase' => $purchase,
            'detail' => $detail,
        ]), [
            'product_id' => $product->id,
            'quantity' => '3.000',
            'unit_price' => '10.00',
            'discount' => '0.00',
            'tax_rate_id' => $taxRate->id,
        ])
        ->assertRedirect(route('purchasing.purchases.show', $purchase));

    expect($purchase->refresh()->total)->toBe('30.00');

    $this->actingAs($user)
        ->delete(route('purchasing.purchases.details.destroy', [
            'purchase' => $purchase,
            'detail' => $detail,
        ]))
        ->assertRedirect(route('purchasing.purchases.show', $purchase));

    expect($purchase->details()->count())->toBe(0);
    expect($purchase->refresh()->total)->toBe('0.00');
});

it('does not allow purchase detail changes after receipt', function () {
    $user = User::factory()->administrator()->create();
    $purchase = Purchase::factory()->received()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->post(route('purchasing.purchases.details.store', $purchase), [
            'product_id' => $product->id,
            'quantity' => '1.000',
            'unit_price' => '10.00',
        ])
        ->assertForbidden();
});

it('manages sale details through nested routes', function () {
    $user = User::factory()->administrator()->create();
    $sale = Sale::factory()->create();
    $product = Product::factory()->create();
    $taxRate = TaxRate::factory()->exempt()->create();

    $this->actingAs($user)
        ->post(route('sales.sales.details.store', $sale), [
            'product_id' => $product->id,
            'quantity' => '2.000',
            'unit_price' => '10.00',
            'discount' => '0.00',
            'tax_rate_id' => $taxRate->id,
        ])
        ->assertRedirect(route('sales.sales.show', $sale));

    $detail = $sale->details()->firstOrFail();
    expect($sale->refresh()->total)->toBe('20.00');

    $this->actingAs($user)
        ->put(route('sales.sales.details.update', [
            'sale' => $sale,
            'detail' => $detail,
        ]), [
            'product_id' => $product->id,
            'quantity' => '4.000',
            'unit_price' => '10.00',
            'discount' => '0.00',
            'tax_rate_id' => $taxRate->id,
        ])
        ->assertRedirect(route('sales.sales.show', $sale));

    expect($sale->refresh()->total)->toBe('40.00');

    $this->actingAs($user)
        ->delete(route('sales.sales.details.destroy', [
            'sale' => $sale,
            'detail' => $detail,
        ]))
        ->assertRedirect(route('sales.sales.show', $sale));

    expect($sale->details()->count())->toBe(0);
});

it('does not allow sale detail changes after confirmation', function () {
    $user = User::factory()->administrator()->create();
    $sale = Sale::factory()->confirmed()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->post(route('sales.sales.details.store', $sale), [
            'product_id' => $product->id,
            'quantity' => '1.000',
            'unit_price' => '10.00',
        ])
        ->assertForbidden();
});

it('pays a purchase through the dedicated controller', function () {
    $user = User::factory()->administrator()->create();
    $warehouse = Warehouse::factory()->create();
    $purchase = Purchase::factory()->create(['warehouse_id' => $warehouse->id]);
    $product = Product::factory()->create();

    PurchaseDetail::factory()->forPurchase($purchase)->create([
        'product_id' => $product->id,
        'quantity' => '2.000',
        'unit_price' => '10.00',
        'tax_rate' => '0.0000',
        'discount' => '0.00',
    ]);
    $purchase->update(['total' => '20.00', 'balance' => '20.00']);

    $this->actingAs($user)
        ->post(route('purchasing.purchases.pay', $purchase), [
            'payments' => [[
                'payment_method' => 'CASH',
                'amount' => '20.00',
                'reference' => 'PUR-PAY-1',
            ]],
        ])
        ->assertRedirect(route('purchasing.purchases.show', $purchase));

    expect($purchase->refresh()->status->value)->toBe('PAID');
    expect($purchase->isReceived())->toBeTrue();
    expect($purchase->paid_amount)->toBe('20.00');
    expect($purchase->allocations()->count())->toBe(1);
});

it('pays a sale through the dedicated controller', function () {
    $user = User::factory()->administrator()->create();
    $warehouse = Warehouse::factory()->create();
    $sale = Sale::factory()->create(['warehouse_id' => $warehouse->id]);
    $product = Product::factory()->create();

    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '5.000',
    ]);

    SaleDetail::factory()->forSale($sale)->create([
        'product_id' => $product->id,
        'quantity' => '1.000',
        'unit_price' => '10.00',
        'tax_rate' => '0.0000',
        'discount' => '0.00',
    ]);
    $sale->update(['total' => '10.00', 'balance' => '10.00']);

    $this->actingAs($user)
        ->post(route('sales.sales.pay', $sale), [
            'payments' => [[
                'payment_method' => 'CASH',
                'amount' => '10.00',
                'reference' => 'SALE-PAY-1',
            ]],
        ])
        ->assertRedirect(route('sales.sales.show', $sale));

    expect($sale->refresh()->status->value)->toBe('PAID');
    expect($sale->paid_amount)->toBe('10.00');
    expect($sale->allocations()->count())->toBe(1);
});

it('shows a clear error when paying a sale without enough stock', function () {
    $user = User::factory()->administrator()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $sale = Sale::factory()->create(['warehouse_id' => $warehouse->id]);

    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '1.000',
    ]);

    SaleDetail::factory()->forSale($sale)->create([
        'product_id' => $product->id,
        'quantity' => '3.000',
        'unit_price' => '10.00',
        'tax_rate' => '0.0000',
        'discount' => '0.00',
    ]);
    $sale->update(['total' => '30.00', 'balance' => '30.00']);

    $this->actingAs($user)
        ->from(route('sales.sales.show', $sale))
        ->post(route('sales.sales.pay', $sale), [
            'payments' => [[
                'payment_method' => 'CASH',
                'amount' => '30.00',
            ]],
        ])
        ->assertRedirect(route('sales.sales.show', $sale))
        ->assertSessionHas('error', 'No hay existencias suficientes en el almacén.');

    expect($sale->refresh()->isDraft())->toBeTrue();
});

it('pays a sale with multiple payment methods', function () {
    $user = User::factory()->administrator()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $sale = Sale::factory()->create([
        'warehouse_id' => $warehouse->id,
        'total' => '100.00',
        'balance' => '100.00',
    ]);

    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '2.000',
    ]);

    SaleDetail::factory()->forSale($sale)->create([
        'product_id' => $product->id,
        'quantity' => '1.000',
        'unit_price' => '100.00',
        'tax_rate' => '0.0000',
        'discount' => '0.00',
    ]);

    $this->actingAs($user)
        ->post(route('sales.sales.pay', $sale), [
            'payments' => [
                [
                    'payment_method' => 'CASH',
                    'amount' => '60.00',
                    'reference' => 'CAJA-1',
                ],
                [
                    'payment_method' => 'CARD',
                    'amount' => '40.00',
                    'reference' => 'TARJETA-1',
                ],
            ],
        ])
        ->assertRedirect(route('sales.sales.show', $sale));

    expect($sale->refresh()->status->value)->toBe('PAID');
    expect($sale->allocations()->count())->toBe(2);
    expect($sale->allocations()->with('payment')->get()->pluck('payment.payment_method')->map->value->sort()->values()->all())
        ->toBe(['CARD', 'CASH']);
});

it('rejects a payment total that does not match the sale total', function () {
    $user = User::factory()->administrator()->create();
    $sale = Sale::factory()->create([
        'total' => '100.00',
        'balance' => '100.00',
    ]);

    $this->actingAs($user)
        ->post(route('sales.sales.pay', $sale), [
            'payments' => [[
                'payment_method' => 'CASH',
                'amount' => '90.00',
            ]],
        ])
        ->assertSessionHasErrors('payments');

    expect($sale->refresh()->status->value)->toBe('DRAFT');
    expect($sale->allocations()->count())->toBe(0);
});
