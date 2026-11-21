<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Database\Eloquent\Model;

class SaleReturnPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'sales.returns';
    }

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, Model $model): bool
    {
        return $model instanceof SaleReturn
            && $this->can($user, 'view')
            && $this->belongsToCurrentTenant($model);
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function confirm(User $user, SaleReturn $return): bool
    {
        return $this->belongsToCurrentTenant($return)
            && $return->isDraft()
            && $this->can($user, 'confirm');
    }

    public function cancel(User $user, SaleReturn $return): bool
    {
        return $this->belongsToCurrentTenant($return)
            && $return->isConfirmed()
            && $this->can($user, 'cancel');
    }
}
