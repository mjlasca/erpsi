<?php

namespace App\Modules\Purchasing\Resources;

use App\Modules\Administration\Resources\UserResource;
use App\Modules\Inventory\Resources\WarehouseResource;
use App\Modules\Payments\Resources\PaymentAllocationResource;
use App\Modules\Purchasing\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Purchase
 */
class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_number' => $this->purchase_number,
            'supplier_id' => $this->supplier_id,
            'supplier_invoice' => $this->supplier_invoice,
            'warehouse_id' => $this->warehouse_id,
            'purchase_date' => $this->purchase_date,
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
            'received_at' => $this->received_at,
            'cancelled_at' => $this->cancelled_at,
            'supplier' => $this->whenLoaded('supplier', fn (): array => (new SupplierResource($this->supplier))->resolve()),
            'warehouse' => $this->whenLoaded('warehouse', fn (): array => (new WarehouseResource($this->warehouse))->resolve()),
            'creator' => $this->whenLoaded('creator', fn (): array => (new UserResource($this->creator))->resolve()),
            'details' => $this->whenLoaded('details', fn (): array => PurchaseDetailResource::collection($this->details)->resolve()),
            'allocations' => $this->whenLoaded('allocations', fn (): array => PaymentAllocationResource::collection($this->allocations)->resolve()),
            'details_count' => $this->whenCounted('details'),
        ];
    }
}
