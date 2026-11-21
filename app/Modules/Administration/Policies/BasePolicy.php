<?php

namespace App\Modules\Administration\Policies;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    abstract protected function permissionPrefix(): string;

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, $model, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, $model, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, $model, 'delete');
    }

    /**
     * Authorize an action on a concrete model, re-checking tenant ownership.
     */
    protected function allows(User $user, Model $model, string $action): bool
    {
        return $this->belongsToCurrentTenant($model) && $this->can($user, $action);
    }

    /**
     * Route model binding resolves models outside the tenant global scope, so
     * ownership is verified again before allowing any action on a model.
     *
     * Without a resolved tenant there is nothing to compare against: that only
     * happens on platform routes, console commands and the test suite. Any
     * authenticated request outside "platform.*" is rejected earlier by
     * ResolveTenant, so the tenant fence always has a value in production.
     */
    protected function belongsToCurrentTenant(Model $model): bool
    {
        if (! in_array(BelongsToTenant::class, class_uses_recursive($model), true)) {
            return true;
        }

        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            return true;
        }

        return (int) $model->getAttribute('tenant_id') === $tenantId;
    }

    protected function can(User $user, string $action): bool
    {
        return $user->hasPermission($this->permissionPrefix().'.'.$action);
    }
}
