<?php

namespace App\Modules\Catalog\Requests;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('product_variants', 'sku')->ignore($variant)],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('product_variants', 'name')
                    ->where(fn ($query) => $query->where('product_id', $this->integer('product_id')))
                    ->ignore($variant),
            ],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'barcode')->ignore($variant)],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
