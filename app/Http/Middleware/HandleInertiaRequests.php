<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use App\Modules\Administration\Support\Permissions;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $context = app(TenantContext::class);
        $tenant = $context->tenant();
        $role = $user?->currentRole();

        $can = [];

        if ($user !== null) {
            foreach (Permissions::all() as $permission) {
                $can[$permission] = $user->hasPermission($permission);
            }
        }

        return [
            ...parent::share($request),
            'name' => PlatformSetting::safeValue('platform_name', (string) config('app.name')),
            'can' => $can,
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'role' => $role === null ? null : [
                        'id' => $role->id,
                        'name' => $role->name,
                    ],
                    'permissions' => $role === null ? [] : $role->permissions,
                    'is_platform_admin' => $user->is_platform_admin,
                    'locale' => $user->locale,
                    'timezone' => $user->timezone,
                ],
            ],
            'locale' => $context->locale(),
            'timezone' => $user === null ? $context->timezone() : ($user->timezone ?? $context->timezone()),
            'currency' => $tenant === null ? config('app.default_currency', 'MXN') : $tenant->currency,
            'supportedLocales' => config('app.supported_locales', ['es', 'en']),
            'tenant' => $tenant === null ? null : [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'locale' => $tenant->locale,
                'timezone' => $tenant->timezone,
                'currency' => $tenant->currency,
                'logo_url' => $tenant->logo_path === null
                    ? null
                    : (str_starts_with($tenant->logo_path, 'http') ? $tenant->logo_path : Storage::disk('public')->url($tenant->logo_path)),
            ],
            'supportMode' => $context->isSupportMode(),
            'supportTenant' => $context->isSupportMode() && $tenant !== null
                ? ['id' => $tenant->id, 'name' => $tenant->name]
                : null,
            'tenants' => $user === null ? [] : $user->tenants()
                ->wherePivot('is_active', true)
                ->get(['tenants.id', 'tenants.name', 'tenants.slug'])
                ->map(fn ($item): array => ['id' => $item->id, 'name' => $item->name])
                ->values()
                ->all(),
            'flash' => [
                'success' => fn (): mixed => $request->session()->get('success'),
                'error' => fn (): mixed => $request->session()->get('error'),
            ],
        ];
    }
}
