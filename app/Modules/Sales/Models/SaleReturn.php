<?php

namespace App\Modules\Sales\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use App\Modules\Sales\Enums\ReturnStatus;
use App\Modules\Sales\Policies\SaleReturnPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sale_id
 * @property string $return_number
 * @property Carbon $return_date
 * @property ReturnStatus $status
 * @property string|null $reason
 * @property string $total
 * @property string $credited_amount
 * @property string $refunded_amount
 * @property string|null $refund_method
 */
#[Fillable([
    'sale_id',
    'return_number',
    'return_date',
    'status',
    'reason',
    'subtotal',
    'tax_amount',
    'total',
    'credited_amount',
    'refunded_amount',
    'refund_method',
    'notes',
    'created_by',
    'confirmed_at',
    'cancelled_at',
])]
#[UsePolicy(SaleReturnPolicy::class)]
class SaleReturn extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'status' => ReturnStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'credited_amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return HasMany<SaleReturnDetail, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(SaleReturnDetail::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === ReturnStatus::Draft;
    }

    public function isConfirmed(): bool
    {
        return $this->status === ReturnStatus::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === ReturnStatus::Cancelled;
    }
}
