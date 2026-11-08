<?php

namespace App\Modules\Catalog\Resources;

use App\Modules\Catalog\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'product_type' => $this->product_type->value,
            'product_type_label' => $this->product_type->label(),
            'brand' => $this->brand,
            'barcode' => $this->barcode,
            'cost_price' => $this->cost_price,
            'sale_price' => $this->sale_price,
            'min_stock' => $this->min_stock,
            'is_inventory' => $this->is_inventory,
            'track_lots' => $this->track_lots,
            'is_active' => $this->is_active,
            'category' => $this->whenLoaded('category', fn (): array => (new ProductCategoryResource($this->category))->resolve()),
            'unit' => $this->whenLoaded('unit', fn (): array => (new UnitResource($this->unit))->resolve()),
            'variants' => $this->whenLoaded('variants', fn (): array => ProductVariantResource::collection($this->variants)->resolve()),
            'attributes' => $this->whenLoaded('attributes', fn (): array => ProductAttributeResource::collection($this->attributes)->resolve()),
            'variants_count' => $this->whenCounted('variants'),
        ];
    }
}
