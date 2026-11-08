<?php

namespace App\Modules\Administration\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'is_active' => $this->is_active,
            'role' => $this->whenLoaded('role', fn (): array => (new RoleResource($this->role))->resolve()),
            'created_at' => $this->created_at,
        ];
    }
}
