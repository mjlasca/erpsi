<?php

namespace App\Modules\Sales\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Sales\Enums\ReturnDisposition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sale_return_id
 * @property int $sale_detail_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $inventory_lot_id
 * @property string $quantity
 * @property string $unit_price
 * @property string $discount
 * @property string $tax_amount
 * @property string $subtotal
 * @property string $total
 * @property ReturnDisposition $disposition
 */
#[Fillable([
    'sale_return_id',
    'sale_detail_id',
    'product_id',
    'product_variant_id',
    'inventory_lot_id',
    'quantity',
    'unit_price',
    'discount',
    'tax_rate',
    'tax_amount',
    'subtotal',
    'total',
    'disposition',
])]
class SaleReturnDetail extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'disposition' => ReturnDisposition::class,
        ];
    }

    /** @return BelongsTo<SaleReturn, $this> */
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    /** @return BelongsTo<SaleDetail, $this> */
    public function saleDetail(): BelongsTo
    {
        return $this->belongsTo(SaleDetail::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** @return BelongsTo<InventoryLot, $this> */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}
