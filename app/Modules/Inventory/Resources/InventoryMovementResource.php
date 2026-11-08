<?php

namespace App\Modules\Inventory\Resources;

use App\Modules\Administration\Resources\UserResource;
use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Catalog\Resources\ProductVariantResource;
use App\Modules\Inventory\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryMovement
 */
class InventoryMovementResource extends JsonResource
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
            'inventory_lot_id' => $this->inventory_lot_id,
            'movement_type' => $this->movement_type->value,
            'movement_type_label' => $this->movement_type->label(),
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'movement_date' => $this->movement_date,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'product' => $this->whenLoaded('product', fn (): array => (new ProductResource($this->product))->resolve()),
            'variant' => $this->whenLoaded('variant', fn (): array => (new ProductVariantResource($this->variant))->resolve()),
            'warehouse' => $this->whenLoaded('warehouse', fn (): array => (new WarehouseResource($this->warehouse))->resolve()),
            'lot' => $this->whenLoaded('lot', fn (): array => (new InventoryLotResource($this->lot))->resolve()),
            'creator' => $this->whenLoaded('creator', fn (): array => (new UserResource($this->creator))->resolve()),
        ];
    }
}
