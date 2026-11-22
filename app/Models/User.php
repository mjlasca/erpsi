<?php

namespace App\Models;

use App\Modules\Administration\Models\Role;
use App\Modules\Administration\Policies\UserPolicy;
use App\Support\Tenancy\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Contracts\OAuthenticatable;

/**
 * @property int $id
 * @property int|null $role_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_active
 * @property string|null $locale
 * @property string|null $timezone
 * @property bool $is_platform_admin
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role|null $role
 */
#[Fillable(['name', 'email', 'password', 'role_id', 'is_active', 'locale', 'timezone', 'is_platform_admin'])]
#[Hidden(['password', 'remember_token'])]
#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_platform_admin' => 'boolean',
        ];
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsToMany<Tenant, $this> */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot(['role_id', 'is_default', 'is_active'])
            ->withTimestamps();
    }

    public function currentRole(): ?Role
    {
        $context = app(TenantContext::class);
        $tenantId = $context->id();

        if ($tenantId !== null && $context->isSupportMode()) {
            return Role::query()->where('name', 'Administrator')->first();
        }

        if ($tenantId !== null) {
            $roleId = DB::table('tenant_user')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $this->id)
                ->where('is_active', true)
                ->value('role_id');

            if ($roleId !== null) {
                return Role::query()->find((int) $roleId);
            }
        }

        return $this->relationLoaded('role') ? $this->role : $this->role()->first();
    }

    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenants()
            ->where('tenants.id', $tenantId)
            ->wherePivot('is_active', true)
            ->exists();
    }

    public function hasRole(string $role): bool
    {
        return $this->currentRole()?->name === $role;
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->currentRole()?->hasPermission($permission) ?? false;
    }

    public function isAdministrator(): bool
    {
        return $this->hasPermission('*');
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
