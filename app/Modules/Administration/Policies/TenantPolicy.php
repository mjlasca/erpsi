<?php

namespace App\Modules\Administration\Policies;

class TenantPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'administration.settings';
    }
}
