<?php

namespace App\Modules\Catalog\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Catalog\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'catalog.categories';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof ProductCategory || ! $this->can($user, 'delete')) {
            return false;
        }

        return ! $model->products()->exists() && ! $model->children()->exists();
    }
}
