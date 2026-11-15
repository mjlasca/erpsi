<?php

namespace App\Modules\Cash\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use App\Modules\Cash\Enums\CashSessionStatus;
use App\Modules\Cash\Policies\CashSessionPolicy;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $session_number
 * @property Carbon $business_date
 * @property CashSessionStatus $status
 * @property string $opening_balance
 * @property string $expected_cash
 * @property string|null $counted_cash
 * @property string|null $difference
 */
#[Fillable([
    'session_number',
    'business_date',
    'warehouse_id',
    'status',
    'opening_balance',
    'expected_cash',
    'counted_cash',
    'difference',
    'notes',
    'opened_by',
    'closed_by',
    'opened_at',
    'closed_at',
])]
#[UsePolicy(CashSessionPolicy::class)]
class CashSession extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'status' => CashSessionStatus::class,
            'opening_balance' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<User, $this> */
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /** @return BelongsTo<User, $this> */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return HasMany<InventorySnapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(InventorySnapshot::class);
    }

    public function isOpen(): bool
    {
        return $this->status === CashSessionStatus::Open;
    }

    public static function openForDate(string $date): ?self
    {
        return self::query()
            ->where('status', CashSessionStatus::Open)
            ->whereDate('business_date', $date)
            ->latest('id')
            ->first();
    }
}
