<?php

namespace App\Models;

use App\Modules\Administration\Policies\TenantPolicy;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Inventory\Models\Warehouse;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $legal_name
 * @property string|null $tax_id
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $logo_path
 * @property string $locale
 * @property string $timezone
 * @property string $currency
 * @property string $date_format
 * @property int $week_starts_on
 * @property int|null $plan_id
 * @property int|null $owner_id
 * @property string $subscription_status
 * @property string|null $billing_email
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $subscription_starts_at
 * @property Carbon|null $subscription_ends_at
 * @property Carbon|null $suspended_at
 * @property string|null $suspension_reason
 * @property Carbon|null $last_activity_at
 * @property TenantMembership|null $pivot
 * @property string $invoice_prefix
 * @property string $purchase_prefix
 * @property string $sale_return_prefix
 * @property string $purchase_return_prefix
 * @property string $cash_session_prefix
 * @property int|null $default_warehouse_id
 * @property bool $is_active
 */
#[Fillable([
    'name',
    'slug',
    'legal_name',
    'tax_id',
    'email',
    'phone',
    'address',
    'logo_path',
    'locale',
    'timezone',
    'currency',
    'date_format',
    'week_starts_on',
    'invoice_prefix',
    'purchase_prefix',
    'sale_return_prefix',
    'purchase_return_prefix',
    'cash_session_prefix',
    'default_warehouse_id',
    'is_active',
    'plan_id',
    'owner_id',
    'subscription_status',
    'billing_email',
    'trial_ends_at',
    'subscription_starts_at',
    'subscription_ends_at',
    'suspended_at',
    'suspension_reason',
    'last_activity_at',
])]
#[UsePolicy(TenantPolicy::class)]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'week_starts_on' => 'integer',
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
            'subscription_starts_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'is_default', 'is_active'])
            ->withTimestamps();
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /** @return HasMany<CashSession, $this> */
    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }
}
