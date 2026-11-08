<?php

namespace App\Modules\Catalog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'unit_type' => $this->unit_type?->value,
            'unit_type_label' => $this->unit_type?->label(),
            'allows_decimal' => $this->allows_decimal,
            'is_active' => $this->is_active,
        ];
    }
}
