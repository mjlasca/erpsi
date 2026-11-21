<?php

namespace App\Modules\Catalog\Requests;

use App\Modules\Catalog\Enums\UnitType;
use App\Support\Tenancy\TenantUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
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
        $unit = $this->route('unit');

        return [
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10', TenantUnique::make('units', 'abbreviation', $unit)],
            'unit_type' => ['nullable', Rule::enum(UnitType::class)],
            'allows_decimal' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
