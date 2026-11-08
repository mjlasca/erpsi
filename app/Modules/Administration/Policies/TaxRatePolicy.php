<?php

namespace App\Modules\Administration\Policies;

use App\Models\User;
use App\Modules\Administration\Models\TaxRate;
use Illuminate\Database\Eloquent\Model;

class TaxRatePolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'administration.tax_rates';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof TaxRate || ! $this->can($user, 'delete')) {
            return false;
        }

        return ! $model->purchaseDetails()->exists() && ! $model->saleDetails()->exists();
    }
}
