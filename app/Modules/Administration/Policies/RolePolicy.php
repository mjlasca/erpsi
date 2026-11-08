<?php

namespace App\Modules\Administration\Policies;

use App\Models\User;
use App\Modules\Administration\Models\Role;
use Illuminate\Database\Eloquent\Model;

class RolePolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'administration.roles';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof Role || ! $this->can($user, 'delete')) {
            return false;
        }

        return $model->name !== 'Administrator' && ! $model->users()->exists();
    }
}
