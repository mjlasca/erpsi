<?php

namespace App\Modules\Catalog\Policies;

use App\Modules\Administration\Policies\BasePolicy;

class ProductAttributePolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'catalog.attributes';
    }
}
