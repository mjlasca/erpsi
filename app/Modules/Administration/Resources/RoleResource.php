<?php

namespace App\Modules\Administration\Resources;

use App\Modules\Administration\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissions ?? [],
            'permission_groups' => Permissions::groups(),
            'is_active' => $this->is_active,
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
