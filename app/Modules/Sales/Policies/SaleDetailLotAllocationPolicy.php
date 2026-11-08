<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;

class SaleDetailLotAllocationPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'sales.sales';
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
