<?php

namespace App\Modules\Sales\Requests;

use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Sales\Enums\ReturnDisposition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleReturnRequest extends FormRequest
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
            'details.*.sale_detail_id' => ['required', 'integer', 'exists:sale_details,id'],
            'details.*.quantity' => ['required', 'numeric', 'gt:0'],
            'details.*.disposition' => ['required', Rule::enum(ReturnDisposition::class)],
            'details.*.inventory_lot_id' => ['nullable', 'integer', 'exists:inventory_lots,id'],
        ];
    }
}
