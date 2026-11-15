<?php

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Purchasing\Actions\ConfirmPurchaseReturn;
use App\Modules\Purchasing\Actions\ReceivePurchase;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseDetail;
use App\Modules\Purchasing\Models\PurchaseReturn;
use App\Modules\Purchasing\Models\PurchaseReturnDetail;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Sales\Enums\ReturnStatus;

it('returns purchased goods to the supplier and reduces the payable balance', function () {
    $warehouse = Warehouse::factory()->create();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create();

    $purchase = Purchase::factory()->create([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);
    $detail = PurchaseDetail::factory()->forPurchase($purchase)->create([
        'product_id' => $product->id,
        'quantity' => '5.000',
        'unit_price' => '10.00',
        'discount' => '0.00',
        'tax_rate' => '0.0000',
        'tax_rate_id' => null,
    ]);

    app(ReceivePurchase::class)->execute($purchase);
    $purchase->refresh();

    expect($purchase->status)->toBe(PurchaseStatus::Received);
    expect($purchase->balance)->toBe('50.00');

    $return = PurchaseReturn::query()->create([
        'purchase_id' => $purchase->id,
        'return_number' => 'RCOMP-TEST-'.uniqid(),
        'return_date' => now()->toDateString(),
        'status' => ReturnStatus::Draft,
    ]);
    PurchaseReturnDetail::query()->create([
        'purchase_return_id' => $return->id,
        'purchase_detail_id' => $detail->id,
        'product_id' => $product->id,
        'quantity' => '2.000',
        'unit_price' => '10.00',
        'discount' => '0.00',
        'tax_rate' => '0.0000',
        'tax_amount' => '0.00',
        'subtotal' => '0.00',
        'total' => '0.00',
    ]);

    $confirmed = app(ConfirmPurchaseReturn::class)->execute($return);

    expect($confirmed->status)->toBe(ReturnStatus::Confirmed);
    expect($confirmed->total)->toBe('20.00');
    expect($purchase->refresh()->returned_amount)->toBe('20.00');
    expect($purchase->balance)->toBe('30.00');

    $stock = InventoryStock::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();

    expect($stock->quantity_on_hand)->toBe('3.000');
});
