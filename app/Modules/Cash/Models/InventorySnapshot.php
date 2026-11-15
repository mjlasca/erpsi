<?php

namespace App\Modules\Cash\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $cash_session_id
 * @property string $type
 * @property string $captured_at
 */
#[Fillable(['cash_session_id', 'type', 'captured_at', 'captured_by'])]
class InventorySnapshot extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return ['captured_at' => 'datetime'];
    }

    /** @return BelongsTo<CashSession, $this> */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /** @return BelongsTo<User, $this> */
    public function capturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }

    /** @return HasMany<InventorySnapshotLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InventorySnapshotLine::class);
    }
}
