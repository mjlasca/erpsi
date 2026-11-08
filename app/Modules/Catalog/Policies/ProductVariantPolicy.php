<?php

namespace App\Modules\Catalog\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class ProductVariantPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'catalog.variants';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof ProductVariant || ! $this->can($user, 'delete')) {
            return false;
        }

        return ! $model->stockItems()->exists()
            && ! $model->movements()->exists()
            && ! $model->purchaseDetails()->exists()
            && ! $model->saleDetails()->exists()
            && ! $model->lots()->exists();
    }
}
