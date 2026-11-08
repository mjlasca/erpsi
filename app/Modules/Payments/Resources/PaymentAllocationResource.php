<?php

namespace App\Modules\Payments\Resources;

use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Purchasing\Resources\PurchaseResource;
use App\Modules\Sales\Resources\SaleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PaymentAllocation
 */
class PaymentAllocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'sale_id' => $this->sale_id,
            'purchase_id' => $this->purchase_id,
            'amount' => $this->amount,
            'payment' => $this->whenLoaded('payment', fn (): array => (new PaymentResource($this->payment))->resolve()),
            'sale' => $this->whenLoaded('sale', fn (): array => (new SaleResource($this->sale))->resolve()),
            'purchase' => $this->whenLoaded('purchase', fn (): array => (new PurchaseResource($this->purchase))->resolve()),
        ];
    }
}
