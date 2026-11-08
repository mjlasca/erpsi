<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

class WarehousePolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'inventory.warehouses';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof Warehouse || ! $this->can($user, 'delete')) {
            return false;
        }

        return ! $model->stockItems()->exists()
            && ! $model->movements()->exists()
            && ! $model->lots()->exists()
            && ! $model->purchases()->exists()
            && ! $model->sales()->exists();
    }
}
