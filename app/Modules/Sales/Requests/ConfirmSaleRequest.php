<?php

namespace App\Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmSaleRequest extends FormRequest
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
            'lot_allocations' => ['sometimes', 'array'],
            'lot_allocations.*' => ['array'],
            'lot_allocations.*.*' => ['numeric', 'gt:0'],
        ];
    }
}
