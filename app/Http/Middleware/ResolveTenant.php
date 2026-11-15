<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ResolveTenant
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->set(null);

        try {
            $supportedLocales = PlatformSetting::value('supported_locales');
            $defaultLocale = PlatformSetting::value('default_locale');
        } catch (Throwable) {
            $supportedLocales = null;
            $defaultLocale = null;
        }

        if ($supportedLocales !== null) {
            config(['app.supported_locales' => array_values(array_filter(explode(',', $supportedLocales)))]);
        }

        if ($defaultLocale !== null && in_array($defaultLocale, config('app.supported_locales', ['es', 'en']), true)) {
            app()->setLocale($defaultLocale);
        }

        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $supportTenantId = (int) $request->session()->get('support_tenant_id', 0);
        $supportMode = false;
        $tenant = null;

        if ($supportTenantId > 0 && $user->is_platform_admin) {
            $tenant = Tenant::query()
                ->whereKey($supportTenantId)
                ->where('is_active', true)
                ->first();

            if ($tenant === null) {
                $request->session()->forget(['support_tenant_id', 'support_started_at']);
            } else {
                $supportMode = true;
            }
        }

        $tenant ??= $this->resolveTenant($request);

        if ($tenant !== null) {
            $this->context->set($tenant);
            $this->context->setSupportMode($supportMode);
            app()->setLocale($this->context->locale());
        } elseif (! $request->routeIs('platform.*') && ! app()->runningUnitTests()) {
            abort(403, 'Tu usuario no tiene una empresa activa asignada.');
        }

        return $next($request);
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        $user = $request->user();
        $sessionTenantId = (int) $request->session()->get('tenant_id', 0);

        if ($sessionTenantId > 0 && $user->belongsToTenant($sessionTenantId)) {
            return Tenant::query()->whereKey($sessionTenantId)->where('is_active', true)->first();
        }

        $tenant = $user->tenants()
            ->wherePivot('is_active', true)
            ->where('tenants.is_active', true)
            ->orderByDesc('tenant_user.is_default')
            ->orderBy('tenants.id')
            ->first();

        if ($tenant !== null) {
            $request->session()->put('tenant_id', $tenant->id);
        }

        return $tenant;
    }
}
