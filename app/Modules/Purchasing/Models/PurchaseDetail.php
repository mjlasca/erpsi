<?php

namespace App\Modules\Purchasing\Models;

use App\Modules\Administration\Models\TaxRate;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLot;
use Database\Factories\PurchaseDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $purchase_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property string $quantity
 * @property string $unit_price
 * @property string $discount
 * @property int|null $tax_rate_id
 * @property string $tax_rate
 * @property string $tax_amount
 * @property string $subtotal
 * @property string $total
 */
#[Fillable([
    'purchase_id',
    'product_id',
    'product_variant_id',
    'quantity',
    'unit_price',
    'discount',
    'tax_rate_id',
    'tax_rate',
    'tax_amount',
    'subtotal',
    'total',
])]
#[UseFactory(PurchaseDetailFactory::class)]
class PurchaseDetail extends Model
{
    /** @use HasFactory<PurchaseDetailFactory> */
    use HasFactory;

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
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
    }
}
