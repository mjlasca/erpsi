<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Sales\Models\SaleDetailLotAllocation;
use Illuminate\Database\Eloquent\Model;

class SaleDetailLotAllocationPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'sales.sales';
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('sales.sales.update');
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof SaleDetailLotAllocation
            && $model->saleDetail->sale->isDraft()
            && $user->hasPermission('sales.sales.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof SaleDetailLotAllocation
            && $model->saleDetail->sale->isDraft()
            && $user->hasPermission('sales.sales.update');
    }
}
