<?php

namespace App\Modules\Sales\Requests;

use App\Support\Tenancy\TenantUnique;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
        $customer = $this->route('customer');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'tax_id' => ['nullable', 'string', 'max:50', TenantUnique::make('customers', 'tax_id', $customer)],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
