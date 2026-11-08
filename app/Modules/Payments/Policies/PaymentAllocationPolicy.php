<?php

namespace App\Modules\Payments\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocationPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'payments.allocations';
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payments.allocations.manage');
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermission('payments.allocations.manage');
    }
}
