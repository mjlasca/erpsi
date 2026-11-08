<?php

namespace App\Modules\Catalog\Resources;

use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductAttribute
 */
class ProductAttributeResource extends JsonResource
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
            'unit_id' => $this->unit_id,
            'name' => $this->name,
            'value' => $this->value,
            'sort_order' => $this->sort_order,
            'unit' => new UnitResource($this->whenLoaded('unit')),
        ];
    }
}
