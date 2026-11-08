<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Purchasing\Models\PurchaseDetail;
use App\Modules\Sales\Models\SaleDetailLotAllocation;
use Database\Factories\InventoryLotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int $warehouse_id
 * @property int|null $purchase_detail_id
 * @property int|null $parent_lot_id
 * @property string $code
 * @property string|null $lot_number
 * @property string|null $serial_number
 * @property string $quantity_initial
 * @property string $quantity_available
 * @property string $unit_cost
 * @property LotStatus $status
 * @property string|null $received_at
 * @property string|null $notes
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'product_id',
    'product_variant_id',
    'warehouse_id',
    'purchase_detail_id',
    'parent_lot_id',
    'code',
    'lot_number',
    'serial_number',
    'quantity_initial',
    'quantity_available',
    'unit_cost',
    'status',
    'received_at',
    'notes',
    'metadata',
])]
#[UseFactory(InventoryLotFactory::class)]
class InventoryLot extends Model
{
    /** @use HasFactory<InventoryLotFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity_initial' => 'decimal:3',
            'quantity_available' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'status' => LotStatus::class,
            'received_at' => 'date',
            'metadata' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseDetail::class);
    }

    public function parentLot(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_lot_id');
    }

    public function childLots(): HasMany
    {
        return $this->hasMany(self::class, 'parent_lot_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SaleDetailLotAllocation::class);
    }

    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('status', LotStatus::Available)
            ->where('quantity_available', '>', 0);
    }
}
