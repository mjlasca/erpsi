<?php

namespace App\Modules\Catalog\Requests;

use App\Modules\Catalog\Enums\UnitType;
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
            'abbreviation' => ['required', 'string', 'max:10', Rule::unique('units', 'abbreviation')->ignore($unit)],
            'unit_type' => ['nullable', Rule::enum(UnitType::class)],
            'allows_decimal' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
