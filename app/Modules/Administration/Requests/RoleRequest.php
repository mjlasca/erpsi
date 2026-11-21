<?php

namespace App\Modules\Administration\Requests;

use App\Modules\Administration\Support\Permissions;
use App\Support\Tenancy\TenantUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:50', TenantUnique::make('roles', 'name', $role)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in([...Permissions::all(), '*'])],
            'is_active' => ['boolean'],
        ];
    }
}
