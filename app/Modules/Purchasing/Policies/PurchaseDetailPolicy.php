<?php

namespace App\Modules\Purchasing\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Purchasing\Models\PurchaseDetail;
use Illuminate\Database\Eloquent\Model;

class PurchaseDetailPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'purchasing.purchases';
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('purchasing.purchases.update');
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof PurchaseDetail
            && $model->purchase->isDraft()
            && $user->hasPermission('purchasing.purchases.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof PurchaseDetail
            && $model->purchase->isDraft()
            && $user->hasPermission('purchasing.purchases.update');
    }
}
