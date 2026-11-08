<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Inventory\Models\InventoryLot;
use Illuminate\Database\Eloquent\Model;

class InventoryLotPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'inventory.lots';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof InventoryLot || ! $this->can($user, 'delete')) {
            return false;
        }

        return ! $model->movements()->exists() && ! $model->allocations()->exists();
    }
}
