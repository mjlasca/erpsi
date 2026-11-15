<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Closure;

/**
 * Holds the tenant resolved for the current request, job or console command.
 *
 * The context is intentionally explicit: tenant-aware models only apply their
 * global scope when a tenant has been resolved.
 */
class TenantContext
{
    protected ?Tenant $tenant = null;

    protected bool $supportMode = false;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;

        if ($tenant === null) {
            $this->supportMode = false;
        }
    }

    public function setSupportMode(bool $supportMode): void
    {
        $this->supportMode = $supportMode;
    }

    public function isSupportMode(): bool
    {
        return $this->supportMode;
    }

    public function setById(?int $tenantId): void
    {
        $this->tenant = $tenantId === null ? null : Tenant::query()->find($tenantId);
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }

    public function timezone(): string
    {
        return $this->tenant === null ? (string) config('app.timezone', 'UTC') : $this->tenant->timezone;
    }

    public function locale(): string
    {
        $userLocale = auth()->user()?->locale;

        if (is_string($userLocale) && in_array($userLocale, config('app.supported_locales', ['es', 'en']), true)) {
            return $userLocale;
        }

        return $this->tenant === null ? (string) config('app.locale', 'es') : $this->tenant->locale;
    }

    public function run(?Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $previousSupportMode = $this->supportMode;
        $this->tenant = $tenant;
        $this->supportMode = false;

        try {
            return $callback();
        } finally {
            $this->tenant = $previous;
            $this->supportMode = $previousSupportMode;
        }
    }
}
