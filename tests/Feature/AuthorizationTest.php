<?php

use App\Models\User;
use App\Modules\Administration\Models\Role;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseDetail;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;
use App\Modules\Sales\Models\SaleDetailLotAllocation;

it('allows users with the required permission to access catalog pages', function () {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user)
        ->get(route('catalog.products.index'))
        ->assertOk();
});

it('denies users without the required permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('catalog.products.index'))
        ->assertForbidden();
});

it('denies access to custom document actions without permission', function () {
    $user = User::factory()->create();
    $sale = Sale::factory()->create(['total' => '100.00', 'balance' => '100.00']);

    $this->actingAs($user)
        ->post(route('sales.sales.pay', $sale), [
            'payments' => [[
                'payment_method' => 'CASH',
                'amount' => '100.00',
            ]],
        ])
        ->assertForbidden();
});

it('keeps sale lot allocations read-only', function () {
    $user = User::factory()->administrator()->create();
    $allocation = SaleDetailLotAllocation::factory()->create();

    expect($user->can('update', $allocation))->toBeFalse();
    expect($user->can('delete', $allocation))->toBeFalse();
});

it('does not allow editing a received purchase', function () {
    $user = User::factory()->administrator()->create();
    $purchase = Purchase::factory()->received()->create();
    $detail = PurchaseDetail::factory()->forPurchase($purchase)->create();

    $this->actingAs($user)
        ->put(route('purchasing.purchases.update', $purchase), [
            'supplier_id' => $purchase->supplier_id,
            'warehouse_id' => $purchase->warehouse_id,
            'purchase_date' => now()->toDateString(),
            'details' => [[
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'unit_price' => $detail->unit_price,
                'discount' => $detail->discount,
                'tax_rate_id' => $detail->tax_rate_id,
            ]],
        ])
        ->assertForbidden();
});

it('does not allow editing a confirmed sale', function () {
    $user = User::factory()->administrator()->create();
    $sale = Sale::factory()->confirmed()->create();
    $detail = SaleDetail::factory()->forSale($sale)->create();

    $this->actingAs($user)
        ->put(route('sales.sales.update', $sale), [
            'customer_id' => $sale->customer_id,
            'warehouse_id' => $sale->warehouse_id,
            'sale_date' => now()->toDateString(),
            'details' => [[
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'unit_price' => $detail->unit_price,
                'discount' => $detail->discount,
                'tax_rate_id' => $detail->tax_rate_id,
            ]],
        ])
        ->assertForbidden();
});

it('applies the sales role permission matrix', function () {
    $role = Role::factory()->sales()->create();
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)->get(route('sales.sales.index'))->assertOk();
    $this->actingAs($user)->get(route('purchasing.purchases.index'))->assertForbidden();
});

it('applies the inventory manager permission matrix', function () {
    $role = Role::factory()->inventoryManager()->create();
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)->get(route('purchasing.purchases.index'))->assertOk();
    $this->actingAs($user)->get(route('sales.sales.create'))->assertForbidden();
});
