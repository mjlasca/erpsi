<?php

namespace App\Modules\Purchasing\Models;

use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use Database\Factories\PurchaseFactory;
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
 * @property string $purchase_number
 * @property int $supplier_id
 * @property string|null $supplier_invoice
 * @property int $warehouse_id
 * @property string $purchase_date
 * @property string $subtotal
 * @property string $discount
 * @property string $tax_amount
 * @property string $total
 * @property string $paid_amount
 * @property string $balance
 * @property PurchaseStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property string|null $received_at
 * @property string|null $cancelled_at
 */
#[Fillable([
    'purchase_number',
    'supplier_id',
    'supplier_invoice',
    'warehouse_id',
    'purchase_date',
    'subtotal',
    'discount',
    'tax_amount',
    'total',
    'paid_amount',
    'balance',
    'status',
    'notes',
    'created_by',
    'received_at',
    'cancelled_at',
])]
#[UseFactory(PurchaseFactory::class)]
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'status' => PurchaseStatus::class,
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<PurchaseDetail, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    /** @return HasMany<PaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function isDraft(): bool
    {
        return $this->status === PurchaseStatus::Draft;
    }

    public function isReceived(): bool
    {
        return $this->status === PurchaseStatus::Received;
    }

    public function isCancelled(): bool
    {
        return $this->status === PurchaseStatus::Cancelled;
    }

    /**
     * @param  Builder<Purchase>  $query
     * @return Builder<Purchase>
     */
    #[Scope]
    protected function draft(Builder $query): Builder
    {
        return $query->where('status', PurchaseStatus::Draft);
    }
}
