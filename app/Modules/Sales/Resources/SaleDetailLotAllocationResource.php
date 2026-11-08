<?php

namespace App\Modules\Sales\Resources;

use App\Modules\Inventory\Resources\InventoryLotResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleDetailLotAllocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_detail_id' => $this->sale_detail_id,
            'inventory_lot_id' => $this->inventory_lot_id,
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'lot' => new InventoryLotResource($this->whenLoaded('lot')),
        ];
    }
}
