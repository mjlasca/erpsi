<?php

namespace App\Modules\Inventory\Requests;

use App\Modules\Inventory\Enums\LotStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $lot = $this->route('lot');
        $creating = $lot === null;

        return [
            'product_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'warehouse_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:warehouses,id'],
            'code' => [$creating ? 'required' : 'sometimes', 'string', 'max:80', Rule::unique('inventory_lots', 'code')->ignore($lot)],
            'lot_number' => ['nullable', 'string', 'max:80'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'quantity_initial' => [$creating ? 'required' : 'sometimes', 'numeric', 'gt:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::enum(LotStatus::class)],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
