<?php

namespace App\Modules\Payments\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Model;

class PaymentPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'payments.payments';
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof Payment
            && ! $model->allocations()->exists()
            && $this->allows($user, $model, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof Payment
            && ! $model->allocations()->exists()
            && $this->allows($user, $model, 'delete');
    }

    public function allocate(User $user, Payment $payment): bool
    {
        return $this->belongsToCurrentTenant($payment)
            && $user->hasPermission('payments.allocations.manage');
    }
}
