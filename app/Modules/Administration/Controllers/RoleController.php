<?php

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\Role;
use App\Modules\Administration\Requests\StoreRoleRequest;
use App\Modules\Administration\Requests\UpdateRoleRequest;
use App\Modules\Administration\Resources\RoleResource;
use App\Modules\Administration\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount('users')
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles->through(fn ($role): array => (new RoleResource($role))->resolve()),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('Admin/Roles/Create', [
            'permissionGroups' => Permissions::groups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validated();
        $validated['permissions'] = $validated['permissions'] ?? [];

        $role = Role::query()->create($validated);

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'Rol creado correctamente.');
    }

    public function show(Role $role): Response
    {
        $this->authorize('view', $role);

        $role->loadCount('users');

        return Inertia::render('Admin/Roles/Show', [
            'role' => (new RoleResource($role))->resolve(),
            'permissionGroups' => Permissions::groups(),
        ]);
    }

    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        return Inertia::render('Admin/Roles/Edit', [
            'role' => (new RoleResource($role))->resolve(),
            'permissionGroups' => Permissions::groups(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validated();
        $validated['permissions'] = $validated['permissions'] ?? [];

        if ($role->name === 'Administrator'
            && ($validated['name'] !== 'Administrator' || ! in_array('*', $validated['permissions'], true))) {
            throw ValidationException::withMessages([
                'name' => 'El rol Administrator debe conservar su nombre y permiso total.',
            ]);
        }

        $role->update($validated);

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->name === 'Administrator') {
            return back()->with('error', 'El rol Administrator está protegido y no se puede eliminar.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rol eliminado correctamente.');
    }
}
