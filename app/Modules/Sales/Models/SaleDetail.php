<?php

namespace App\Modules\Sales\Models;

use App\Modules\Administration\Models\TaxRate;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Sales\Policies\SaleDetailPolicy;
use Database\Factories\SaleDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sale_id
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
    'sale_id',
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
#[UseFactory(SaleDetailFactory::class)]
#[UsePolicy(SaleDetailPolicy::class)]
class SaleDetail extends Model
{
    /** @use HasFactory<SaleDetailFactory> */
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

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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

    /** @return BelongsTo<TaxRate, $this> */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /** @return HasMany<SaleDetailLotAllocation, $this> */
    public function lotAllocations(): HasMany
    {
        return $this->hasMany(SaleDetailLotAllocation::class);
    }
}
