<?php

namespace App\Modules\Sales\Resources;

use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SaleReturn
 */
class SaleReturnResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
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
            'sale' => $this->whenLoaded('sale', fn (): array => (new SaleResource($this->sale))->resolve()),
            'details' => $this->whenLoaded('details', fn (): array => $this->details->map(fn ($detail): array => [
                'id' => $detail->id,
                'sale_detail_id' => $detail->sale_detail_id,
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
                'disposition' => $detail->disposition->value,
                'disposition_label' => $detail->disposition->label(),
                'product' => $detail->product?->only(['id', 'name', 'sku']),
                'variant' => $detail->variant?->only(['id', 'name', 'sku']),
                'lot' => $detail->lot?->only(['id', 'code']),
            ])->all()),
        ];
    }
}
