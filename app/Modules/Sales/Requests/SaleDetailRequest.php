<?php

namespace App\Modules\Sales\Requests;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaleDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('sale');

        return $document instanceof Sale
            && ($this->user()?->can('update', $document) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
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
