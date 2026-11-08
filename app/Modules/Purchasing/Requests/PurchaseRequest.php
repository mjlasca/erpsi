<?php

namespace App\Modules\Purchasing\Requests;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PurchaseRequest extends FormRequest
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
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'supplier_invoice' => ['nullable', 'string', 'max:100'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.id' => ['nullable', 'integer'],
            'details.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'details.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'details.*.quantity' => ['required', 'numeric', 'gt:0'],
            'details.*.unit_price' => ['required', 'numeric', 'min:0'],
            'details.*.discount' => ['nullable', 'numeric', 'min:0'],
            'details.*.tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ((array) $this->input('details', []) as $index => $detail) {
                    $variantId = $detail['product_variant_id'] ?? null;
                    $productId = $detail['product_id'] ?? null;

                    if ($variantId === null || $productId === null) {
                        continue;
                    }

                    if (! ProductVariant::query()->whereKey($variantId)->where('product_id', $productId)->exists()) {
                        $validator->errors()->add(
                            "details.{$index}.product_variant_id",
                            'La variante no pertenece al producto seleccionado.',
                        );
                    }
                }
            },
        ];
    }
}
