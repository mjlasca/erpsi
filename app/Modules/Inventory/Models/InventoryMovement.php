<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Policies\InventoryMovementPolicy;
use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int $warehouse_id
 * @property int|null $inventory_lot_id
 * @property MovementType $movement_type
 * @property string $quantity
 * @property string|null $unit_cost
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string $movement_date
 * @property string|null $notes
 * @property int|null $created_by
 */
#[Fillable([
    'product_id',
    'product_variant_id',
    'warehouse_id',
    'inventory_lot_id',
    'movement_type',
    'quantity',
    'unit_cost',
    'reference_type',
    'reference_id',
    'movement_date',
    'notes',
    'created_by',
])]
#[UseFactory(InventoryMovementFactory::class)]
#[UsePolicy(InventoryMovementPolicy::class)]
class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'movement_type' => MovementType::class,
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'movement_date' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Los movimientos de inventario no se pueden modificar.');
        });

        static::deleting(function (): never {
            throw new LogicException('Los movimientos de inventario no se pueden eliminar.');
        });
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

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
