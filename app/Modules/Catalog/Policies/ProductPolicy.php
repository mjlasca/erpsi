<?php

namespace App\Modules\Catalog\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'catalog.products';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof Product || ! $this->can($user, 'delete')) {
            return false;
        }

        return ! $model->stockItems()->exists()
            && ! $model->movements()->exists()
            && ! $model->purchaseDetails()->exists()
            && ! $model->saleDetails()->exists()
            && ! $model->lots()->exists();
    }
}
