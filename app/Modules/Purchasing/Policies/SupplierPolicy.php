<?php

namespace App\Modules\Purchasing\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class SupplierPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'purchasing.suppliers';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof Supplier || ! $this->can($user, 'delete')) {
            return false;
        }

        return ! $model->purchases()->exists() && ! $model->payments()->exists();
    }
}
