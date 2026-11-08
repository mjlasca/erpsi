<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Sales\Models\SaleDetail;
use Illuminate\Database\Eloquent\Model;

class SaleDetailPolicy extends BasePolicy
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
        return $model instanceof SaleDetail
            && $model->sale->isDraft()
            && $user->hasPermission('sales.sales.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof SaleDetail
            && $model->sale->isDraft()
            && $user->hasPermission('sales.sales.update');
    }
}
