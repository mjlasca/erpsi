<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Policies\SalePolicy;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $invoice_number
 * @property int $customer_id
 * @property int $warehouse_id
 * @property string $sale_date
 * @property string|null $due_date
 * @property string $subtotal
 * @property string $discount
 * @property string $tax_amount
 * @property string $total
 * @property string $paid_amount
 * @property string $balance
 * @property SaleStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property string|null $confirmed_at
 * @property string|null $cancelled_at
 */
#[Fillable([
    'invoice_number',
    'customer_id',
    'warehouse_id',
    'sale_date',
    'due_date',
    'subtotal',
    'discount',
    'tax_amount',
    'total',
    'paid_amount',
    'balance',
    'status',
    'notes',
    'created_by',
    'confirmed_at',
    'cancelled_at',
])]
#[UseFactory(SaleFactory::class)]
#[UsePolicy(SalePolicy::class)]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'status' => SaleStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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

    /** @return HasMany<SaleDetail, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    /** @return HasMany<PaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function isDraft(): bool
    {
        return $this->status === SaleStatus::Draft;
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, [SaleStatus::Confirmed, SaleStatus::Partial, SaleStatus::Paid], true);
    }

    public function isCancelled(): bool
    {
        return $this->status === SaleStatus::Cancelled;
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    #[Scope]
    protected function draft(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::Draft);
    }
}
