<?php

namespace App\Modules\Payments\Models;

use App\Models\User;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Sales\Models\Customer;
use Database\Factories\PaymentFactory;
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
 * @property PaymentType $payment_type
 * @property int|null $customer_id
 * @property int|null $supplier_id
 * @property string $payment_date
 * @property numeric-string $amount
 * @property PaymentMethod $payment_method
 * @property string|null $reference
 * @property string|null $notes
 * @property int|null $created_by
 */
#[Fillable([
    'payment_type',
    'customer_id',
    'supplier_id',
    'payment_date',
    'amount',
    'payment_method',
    'reference',
    'notes',
    'created_by',
])]
#[UseFactory(PaymentFactory::class)]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'payment_type' => PaymentType::class,
            'payment_method' => PaymentMethod::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<PaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function isCustomerPayment(): bool
    {
        return $this->payment_type === PaymentType::CustomerPayment;
    }

    public function isSupplierPayment(): bool
    {
        return $this->payment_type === PaymentType::SupplierPayment;
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    #[Scope]
    protected function customerPayments(Builder $query): Builder
    {
        return $query->where('payment_type', PaymentType::CustomerPayment);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    #[Scope]
    protected function supplierPayments(Builder $query): Builder
    {
        return $query->where('payment_type', PaymentType::SupplierPayment);
    }
}
