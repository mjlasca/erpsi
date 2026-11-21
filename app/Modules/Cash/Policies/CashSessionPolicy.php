<?php

namespace App\Modules\Cash\Policies;

use App\Models\User;
use App\Modules\Administration\Policies\BasePolicy;
use App\Modules\Cash\Models\CashSession;

class CashSessionPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'cash.sessions';
    }

    public function close(User $user, CashSession $session): bool
    {
        return $this->belongsToCurrentTenant($session)
            && $session->isOpen()
            && $this->can($user, 'close');
    }
}
