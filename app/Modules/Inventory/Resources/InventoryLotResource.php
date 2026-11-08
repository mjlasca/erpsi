<?php

namespace App\Modules\Inventory\Resources;

use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Catalog\Resources\ProductVariantResource;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Purchasing\Resources\PurchaseDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryLot
 */
class InventoryLotResource extends JsonResource
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
            'purchase_detail_id' => $this->purchase_detail_id,
            'parent_lot_id' => $this->parent_lot_id,
            'code' => $this->code,
            'lot_number' => $this->lot_number,
            'serial_number' => $this->serial_number,
            'quantity_initial' => $this->quantity_initial,
            'quantity_available' => $this->quantity_available,
            'unit_cost' => $this->unit_cost,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'received_at' => $this->received_at,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'purchase_detail' => new PurchaseDetailResource($this->whenLoaded('purchaseDetail')),
        ];
    }
}
