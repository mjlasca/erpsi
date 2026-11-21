<?php

namespace App\Modules\Inventory\Requests;

use App\Support\Tenancy\TenantExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferInventoryRequest extends FormRequest
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
            'from_warehouse_id' => ['required', 'integer', new TenantExists('warehouses')],
            'to_warehouse_id' => [
                'required',
                'integer',
                new TenantExists('warehouses'),
                Rule::notIn([$this->input('from_warehouse_id')]),
            ],
            'inventory_lot_id' => ['nullable', 'integer', new TenantExists('inventory_lots')],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
