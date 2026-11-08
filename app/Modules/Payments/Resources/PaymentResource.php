<?php

namespace App\Modules\Payments\Resources;

use App\Modules\Administration\Resources\UserResource;
use App\Modules\Purchasing\Resources\SupplierResource;
use App\Modules\Sales\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
                fn (string $carry, $allocation): string => bcadd($carry, (string) $allocation->amount, 2),
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
            'unallocated_amount' => bcsub((string) $this->amount, (string) $allocated, 2),
            'payment_method' => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'allocations' => PaymentAllocationResource::collection($this->whenLoaded('allocations')),
        ];
    }
}
