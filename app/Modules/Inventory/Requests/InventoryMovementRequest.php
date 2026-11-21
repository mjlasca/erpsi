<?php

namespace App\Modules\Inventory\Requests;

use App\Modules\Inventory\Enums\MovementType;
use App\Support\Tenancy\TenantExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryMovementRequest extends FormRequest
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
        return [
            'product_id' => ['required', 'integer', new TenantExists('products')],
            'product_variant_id' => ['nullable', 'integer', new TenantExists('product_variants')],
            'warehouse_id' => ['required', 'integer', new TenantExists('warehouses')],
            'inventory_lot_id' => ['nullable', 'integer', new TenantExists('inventory_lots')],
            'movement_type' => [
                'required',
                Rule::in([
                    MovementType::Return->value,
                    MovementType::Adjustment->value,
                    MovementType::Quarantine->value,
                    MovementType::Damage->value,
                ]),
            ],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'movement_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
