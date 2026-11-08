<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Database\Factories\InventoryStockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int $warehouse_id
 * @property numeric-string $quantity_on_hand
 * @property numeric-string $quantity_reserved
 * @property numeric-string $quantity_minimum
 * @property-read string $quantity_available
 */
#[Table('inventory_stock')]
#[Fillable([
    'product_id',
    'product_variant_id',
    'warehouse_id',
    'quantity_on_hand',
    'quantity_reserved',
    'quantity_minimum',
])]
#[UseFactory(InventoryStockFactory::class)]
class InventoryStock extends Model
{
    /** @use HasFactory<InventoryStockFactory> */
    use HasFactory;

    protected $appends = ['quantity_available'];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:3',
            'quantity_reserved' => 'decimal:3',
            'quantity_minimum' => 'decimal:3',
        ];
    }

    /** @return Attribute<numeric-string, never> */
    protected function quantityAvailable(): Attribute
    {
        return Attribute::get(
            fn (): string => bcsub((string) $this->quantity_on_hand, (string) $this->quantity_reserved, 3),
        );
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

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @param  Builder<InventoryStock>  $query
     * @return Builder<InventoryStock>
     */
    #[Scope]
    protected function lowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'quantity_minimum');
    }
}
