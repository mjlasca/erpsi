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
            && $this->can($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof Payment
            && ! $model->allocations()->exists()
            && $this->can($user, 'delete');
    }

    public function allocate(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.allocations.manage');
    }
}
