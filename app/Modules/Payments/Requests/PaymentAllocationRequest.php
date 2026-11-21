<?php

namespace App\Modules\Payments\Requests;

use App\Support\Tenancy\TenantExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PaymentAllocationRequest extends FormRequest
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
            'sale_id' => ['nullable', 'integer', new TenantExists('sales')],
            'purchase_id' => ['nullable', 'integer', new TenantExists('purchases')],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasSale = $this->filled('sale_id');
                $hasPurchase = $this->filled('purchase_id');

                if ($hasSale === $hasPurchase) {
                    $validator->errors()->add('sale_id', 'Debes seleccionar una venta o una compra.');
                }
            },
        ];
    }
}
