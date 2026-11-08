<?php

namespace App\Modules\Cash\Policies;

use App\Modules\Administration\Policies\BasePolicy;

class CashReconciliationPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'cash.reconciliations';
    }
}
