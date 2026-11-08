<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;

class InventoryStockPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'inventory.stock';
    }

    public function create(User $user): bool
    {
        return false;
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
