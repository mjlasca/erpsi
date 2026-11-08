<?php

namespace App\Modules\Catalog\Requests;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProductAttributeRequest extends FormRequest
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
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'name' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $variantId = $this->input('product_variant_id');
                $productId = $this->input('product_id');

                if ($variantId === null || $productId === null) {
                    return;
                }

                if (! ProductVariant::query()
                    ->whereKey((int) $variantId)
                    ->where('product_id', (int) $productId)
                    ->exists()) {
                    $validator->errors()->add(
                        'product_variant_id',
                        'La variante no pertenece al producto seleccionado.',
                    );
                }
            },
        ];
    }
}
