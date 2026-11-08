<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;

class InventoryMovementPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'inventory.movements';
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }
}
