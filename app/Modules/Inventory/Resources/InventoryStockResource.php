<?php

namespace App\Modules\Inventory\Resources;

use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Catalog\Resources\ProductVariantResource;
use App\Modules\Inventory\Models\InventoryStock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryStock
 */
class InventoryStockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity_on_hand' => $this->quantity_on_hand,
            'quantity_reserved' => $this->quantity_reserved,
            'quantity_minimum' => $this->quantity_minimum,
            'quantity_available' => $this->quantity_available,
            'is_low_stock' => bccomp((string) $this->quantity_on_hand, (string) $this->quantity_minimum, 3) <= 0,
            'product' => $this->whenLoaded('product', fn (): array => (new ProductResource($this->product))->resolve()),
            'variant' => $this->whenLoaded('variant', fn (): array => (new ProductVariantResource($this->variant))->resolve()),
            'warehouse' => $this->whenLoaded('warehouse', fn (): array => (new WarehouseResource($this->warehouse))->resolve()),
        ];
    }
}
