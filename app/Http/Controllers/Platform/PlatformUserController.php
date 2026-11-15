<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Administration\Models\Role;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlatformUserController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = Role::query()->pluck('name', 'id');

        $users = User::query()
            ->with(['tenants:id,name,slug'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('platform_admin'), fn ($query) => $query->where('is_platform_admin', $request->boolean('platform_admin')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Users/Index', [
            'users' => $users->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'is_platform_admin' => $user->is_platform_admin,
                'created_at' => $user->created_at,
                'memberships' => $user->tenants->map(fn ($tenant): array => [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'role' => $roles->get($tenant->pivot->role_id),
                    'is_default' => (bool) $tenant->pivot->is_default,
                    'is_active' => (bool) $tenant->pivot->is_active,
                ])->all(),
            ]),
            'filters' => $request->only('search', 'platform_admin'),
        ]);
    }

    public function togglePlatformAdmin(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        if ($user->is_platform_admin && User::query()->where('is_platform_admin', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'platform_admin' => 'Debe existir al menos un administrador de plataforma.',
            ]);
        }

        $user->update(['is_platform_admin' => ! $user->is_platform_admin]);

        $audit->record(
            $user->is_platform_admin ? 'platform.admin_granted' : 'platform.admin_revoked',
            $user->is_platform_admin
                ? "Acceso de plataforma concedido a {$user->email}."
                : "Acceso de plataforma revocado a {$user->email}.",
            $user,
            null,
            ['user_id' => $user->id],
        );

        return back()->with('success', 'Permisos de plataforma actualizados.');
    }
}
