<?php

namespace App\Modules\Purchasing\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Purchasing\Models\PurchaseReturn;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'purchasing.returns';
    }

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, Model $model): bool
    {
        return $model instanceof PurchaseReturn
            && $this->can($user, 'view')
            && $model->tenant_id === app(TenantContext::class)->id();
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function confirm(User $user, PurchaseReturn $return): bool
    {
        return $return->isDraft() && $this->can($user, 'confirm');
    }

    public function cancel(User $user, PurchaseReturn $return): bool
    {
        return $return->isConfirmed() && $this->can($user, 'cancel');
    }
}
