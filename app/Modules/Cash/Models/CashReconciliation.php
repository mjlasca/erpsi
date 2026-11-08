<?php

namespace App\Modules\Cash\Models;

use App\Models\User;
use App\Modules\Cash\Policies\CashReconciliationPolicy;
use Database\Factories\CashReconciliationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property numeric-string $opening_balance
 * @property numeric-string|null $counted_cash
 * @property string|null $notes
 * @property int|null $created_by
 */
#[Fillable(['date', 'opening_balance', 'counted_cash', 'notes', 'created_by'])]
#[UseFactory(CashReconciliationFactory::class)]
#[UsePolicy(CashReconciliationPolicy::class)]
class CashReconciliation extends Model
{
    /** @use HasFactory<CashReconciliationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'opening_balance' => 'decimal:2',
            'counted_cash' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
