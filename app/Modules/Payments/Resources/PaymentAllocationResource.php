<?php

namespace App\Modules\Payments\Resources;

use App\Modules\Purchasing\Resources\PurchaseResource;
use App\Modules\Sales\Resources\SaleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'sale' => new SaleResource($this->whenLoaded('sale')),
            'purchase' => new PurchaseResource($this->whenLoaded('purchase')),
        ];
    }
}
