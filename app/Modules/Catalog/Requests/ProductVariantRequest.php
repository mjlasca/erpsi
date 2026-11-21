<?php

namespace App\Modules\Catalog\Requests;

use App\Modules\Catalog\Models\Product;
use App\Support\Tenancy\TenantExists;
use App\Support\Tenancy\TenantUnique;
use Illuminate\Foundation\Http\FormRequest;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $product = $this->route('product');

        if ($product instanceof Product) {
            $this->merge(['product_id' => $product->id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $variant = $this->route('variant');

        return [
            'product_id' => ['required', 'integer', new TenantExists('products')],
            'sku' => ['required', 'string', 'max:50', TenantUnique::make('product_variants', 'sku', $variant)],
            'name' => [
                'required',
                'string',
                'max:150',
                TenantUnique::make('product_variants', 'name', $variant)
                    ->where(fn ($query) => $query->where('product_id', $this->integer('product_id'))),
            ],
            'barcode' => ['nullable', 'string', 'max:100', TenantUnique::make('product_variants', 'barcode', $variant)],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
