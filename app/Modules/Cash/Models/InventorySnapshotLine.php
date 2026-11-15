<?php

namespace App\Modules\Cash\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inventory_snapshot_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int $warehouse_id
 * @property int|null $inventory_lot_id
 * @property string $quantity_on_hand
 * @property string $quantity_reserved
 * @property string $quantity_quarantined
 * @property string $quantity_available
 */
#[Fillable([
    'inventory_snapshot_id',
    'product_id',
    'product_variant_id',
    'warehouse_id',
    'inventory_lot_id',
    'quantity_on_hand',
    'quantity_reserved',
    'quantity_quarantined',
    'quantity_available',
])]
class InventorySnapshotLine extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:3',
            'quantity_reserved' => 'decimal:3',
            'quantity_quarantined' => 'decimal:3',
            'quantity_available' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<InventorySnapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(InventorySnapshot::class, 'inventory_snapshot_id');
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

    /** @return BelongsTo<InventoryLot, $this> */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}
