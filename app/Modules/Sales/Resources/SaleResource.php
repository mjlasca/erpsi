<?php

namespace App\Modules\Sales\Resources;

use App\Modules\Administration\Resources\UserResource;
use App\Modules\Inventory\Resources\WarehouseResource;
use App\Modules\Payments\Resources\PaymentAllocationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'sale_date' => $this->sale_date,
            'due_date' => $this->due_date,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'balance' => $this->balance,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'confirmed_at' => $this->confirmed_at,
            'cancelled_at' => $this->cancelled_at,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'details' => SaleDetailResource::collection($this->whenLoaded('details')),
            'allocations' => PaymentAllocationResource::collection($this->whenLoaded('allocations')),
            'details_count' => $this->whenCounted('details'),
        ];
    }
}
