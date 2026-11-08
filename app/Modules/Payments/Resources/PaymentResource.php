<?php

namespace App\Modules\Payments\Resources;

use App\Modules\Administration\Resources\UserResource;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Purchasing\Resources\SupplierResource;
use App\Modules\Sales\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $allocated = '0.00';

        if ($this->relationLoaded('allocations')) {
            $allocated = $this->allocations->reduce(
                fn (string $carry, PaymentAllocation $allocation): string => bcadd($carry, $allocation->amount, 2),
                '0.00',
            );
        }

        return [
            'id' => $this->id,
            'payment_type' => $this->payment_type->value,
            'payment_type_label' => $this->payment_type->label(),
            'customer_id' => $this->customer_id,
            'supplier_id' => $this->supplier_id,
            'payment_date' => $this->payment_date,
            'amount' => $this->amount,
            'allocated_amount' => $allocated,
            'unallocated_amount' => bcsub($this->amount, $allocated, 2),
            'payment_method' => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'customer' => $this->whenLoaded('customer', fn (): array => (new CustomerResource($this->customer))->resolve()),
            'supplier' => $this->whenLoaded('supplier', fn (): array => (new SupplierResource($this->supplier))->resolve()),
            'creator' => $this->whenLoaded('creator', fn (): array => (new UserResource($this->creator))->resolve()),
            'allocations' => $this->whenLoaded('allocations', fn (): array => PaymentAllocationResource::collection($this->allocations)->resolve()),
        ];
    }
}
