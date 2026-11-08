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
            'product' => $this->whenLoaded('product', fn (): array => (new ProductResource($this->product))->resolve()),
            'variant' => $this->whenLoaded('variant', fn (): array => (new ProductVariantResource($this->variant))->resolve()),
            'warehouse' => $this->whenLoaded('warehouse', fn (): array => (new WarehouseResource($this->warehouse))->resolve()),
            'purchase_detail' => $this->whenLoaded('purchaseDetail', fn (): array => (new PurchaseDetailResource($this->purchaseDetail))->resolve()),
        ];
    }
}
