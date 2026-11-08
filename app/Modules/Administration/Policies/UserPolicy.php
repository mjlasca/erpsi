<?php

namespace App\Modules\Administration\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'administration.users';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof User || ! $this->can($user, 'delete') || $user->is($model)) {
            return false;
        }

        if ($model->isAdministrator() && User::query()->whereHas(
            'role',
            fn ($query) => $query->where('name', 'Administrator'),
        )->count() <= 1) {
            return false;
        }

        return true;
    }
}
