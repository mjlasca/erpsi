<?php

namespace App\Modules\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaxRateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_type' => ['nullable', 'string', 'max:30'],
            'jurisdiction' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
