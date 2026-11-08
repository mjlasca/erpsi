<?php

namespace App\Modules\Sales\Models;

use App\Modules\Inventory\Models\InventoryLot;
use Database\Factories\SaleDetailLotAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sale_detail_id
 * @property int $inventory_lot_id
 * @property string $quantity
 * @property string|null $notes
 */
#[Fillable(['sale_detail_id', 'inventory_lot_id', 'quantity', 'notes'])]
#[UseFactory(SaleDetailLotAllocationFactory::class)]
class SaleDetailLotAllocation extends Model
{
    /** @use HasFactory<SaleDetailLotAllocationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function saleDetail(): BelongsTo
    {
        return $this->belongsTo(SaleDetail::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}
