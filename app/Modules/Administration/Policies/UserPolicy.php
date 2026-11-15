<?php

namespace App\Modules\Administration\Policies;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends BasePolicy
{
    protected function permissionPrefix(): string
    {
        return 'administration.users';
    }

    public function view(User $user, Model $model): bool
    {
        return $model instanceof User && $this->sharesTenant($model) && parent::view($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof User && $this->sharesTenant($model) && parent::update($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof User || ! $this->sharesTenant($model) || ! $this->can($user, 'delete') || $user->is($model)) {
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

    protected function sharesTenant(User $model): bool
    {
        $tenantId = app(TenantContext::class)->id();

        return $tenantId === null || $model->belongsToTenant($tenantId);
    }
}
