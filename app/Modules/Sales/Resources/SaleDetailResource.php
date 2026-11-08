<?php

namespace App\Modules\Sales\Resources;

use App\Modules\Administration\Resources\TaxRateResource;
use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Catalog\Resources\ProductVariantResource;
use App\Modules\Sales\Models\SaleDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SaleDetail
 */
class SaleDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount' => $this->discount,
            'tax_rate_id' => $this->tax_rate_id,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'product' => $this->whenLoaded('product', fn (): array => (new ProductResource($this->product))->resolve()),
            'variant' => $this->whenLoaded('variant', fn (): array => (new ProductVariantResource($this->variant))->resolve()),
            'tax_rate_model' => $this->whenLoaded('taxRate', fn (): array => (new TaxRateResource($this->taxRate))->resolve()),
            'lot_allocations' => $this->whenLoaded('lotAllocations', fn (): array => SaleDetailLotAllocationResource::collection($this->lotAllocations)->resolve()),
        ];
    }
}
