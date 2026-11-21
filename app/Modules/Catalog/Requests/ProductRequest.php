<?php

namespace App\Modules\Catalog\Requests;

use App\Modules\Catalog\Enums\ProductType;
use App\Support\Tenancy\TenantExists;
use App\Support\Tenancy\TenantUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
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
        $product = $this->route('product');

        return [
            'sku' => ['required', 'string', 'max:50', TenantUnique::make('products', 'sku', $product)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', new TenantExists('product_categories')],
            'unit_id' => ['required', 'integer', new TenantExists('units')],
            'product_type' => ['required', Rule::enum(ProductType::class)],
            'brand' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100', TenantUnique::make('products', 'barcode', $product)],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'is_inventory' => ['boolean'],
            'track_lots' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('is_inventory') && $this->boolean('track_lots')) {
                    $validator->errors()->add('track_lots', 'Un producto sin inventario no puede manejar lotes.');
                }
            },
        ];
    }
}
