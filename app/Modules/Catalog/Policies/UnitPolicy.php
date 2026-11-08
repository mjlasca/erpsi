<?php

namespace App\Modules\Catalog\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Catalog\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class UnitPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'catalog.units';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof Unit || ! $this->can($user, 'delete')) {
            return false;
        }

        return ! $model->products()->exists();
    }
}
