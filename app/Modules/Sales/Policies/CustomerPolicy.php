<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Sales\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CustomerPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'sales.customers';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof Customer || ! $this->can($user, 'delete')) {
            return false;
        }

        return ! $model->sales()->exists() && ! $model->payments()->exists();
    }
}
