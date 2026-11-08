<?php

namespace App\Modules\Purchasing\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Purchasing\Models\Purchase;
use Illuminate\Database\Eloquent\Model;

class PurchasePolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'purchasing.purchases';
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof Purchase
            && $model->isDraft()
            && $this->can($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof Purchase
            && $model->isDraft()
            && $this->can($user, 'delete');
    }

    public function receive(User $user, Purchase $purchase): bool
    {
        return $purchase->isDraft() && $user->hasPermission('purchasing.purchases.receive');
    }

    public function cancel(User $user, Purchase $purchase): bool
    {
        return ! $purchase->isCancelled()
            && ! $purchase->allocations()->exists()
            && (float) $purchase->paid_amount === 0.0
            && $user->hasPermission('purchasing.purchases.cancel');
    }
}
