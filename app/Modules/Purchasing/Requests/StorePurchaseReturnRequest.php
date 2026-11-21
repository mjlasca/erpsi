<?php

namespace App\Modules\Purchasing\Requests;

use App\Modules\Payments\Enums\PaymentMethod;
use App\Support\Tenancy\TenantExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'details' => ['required', 'array', 'min:1'],
            'details.*.purchase_detail_id' => ['required', 'integer', new TenantExists('purchase_details')],
            'details.*.quantity' => ['required', 'numeric', 'gt:0'],
            'details.*.inventory_lot_id' => ['nullable', 'integer', new TenantExists('inventory_lots')],
        ];
    }
}
