<?php

namespace App\Modules\Purchasing\Resources;

use App\Modules\Purchasing\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseReturn
 */
class PurchaseReturnResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'return_number' => $this->return_number,
            'return_date' => $this->return_date,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'reason' => $this->reason,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'credited_amount' => $this->credited_amount,
            'refunded_amount' => $this->refunded_amount,
            'refund_method' => $this->refund_method,
            'notes' => $this->notes,
            'confirmed_at' => $this->confirmed_at,
            'cancelled_at' => $this->cancelled_at,
            'purchase' => $this->whenLoaded('purchase', fn (): array => (new PurchaseResource($this->purchase))->resolve()),
            'details' => $this->whenLoaded('details', fn (): array => $this->details->map(fn ($detail): array => [
                'id' => $detail->id,
                'purchase_detail_id' => $detail->purchase_detail_id,
                'product_id' => $detail->product_id,
                'product_variant_id' => $detail->product_variant_id,
                'inventory_lot_id' => $detail->inventory_lot_id,
                'quantity' => $detail->quantity,
                'unit_price' => $detail->unit_price,
                'discount' => $detail->discount,
                'tax_rate' => $detail->tax_rate,
                'tax_amount' => $detail->tax_amount,
                'subtotal' => $detail->subtotal,
                'total' => $detail->total,
                'product' => $detail->product?->only(['id', 'name', 'sku']),
                'variant' => $detail->variant?->only(['id', 'name', 'sku']),
                'lot' => $detail->lot?->only(['id', 'code']),
            ])->all()),
        ];
    }
}
