<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;

class SalePolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'sales.sales';
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof Sale
            && $model->isDraft()
            && $this->allows($user, $model, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof Sale
            && $model->isDraft()
            && $this->allows($user, $model, 'delete');
    }

    public function pay(User $user, Sale $sale): bool
    {
        return $this->belongsToCurrentTenant($sale)
            && $sale->isDraft()
            && $user->hasPermission('sales.sales.pay');
    }

    public function cancel(User $user, Sale $sale): bool
    {
        return $this->belongsToCurrentTenant($sale)
            && ! $sale->isCancelled()
            && ! $sale->allocations()->exists()
            && (float) $sale->paid_amount === 0.0
            && $user->hasPermission('sales.sales.cancel');
    }
}
