<?php

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Administration\Models\Role;
use App\Modules\Administration\Requests\UserRequest;
use App\Modules\Administration\Resources\UserResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('role')
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('role_id'), fn ($query, int $roleId) => $query->where('role_id', $roleId))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->through(fn ($user): array => (new UserResource($user))->resolve()),
            'filters' => $request->only('search', 'role_id', 'is_active'),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create($request->validated());
            $user->forceFill(['is_active' => $request->boolean('is_active', true)])->save();

            return $user;
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load('role');

        return Inertia::render('Admin/Users/Show', [
            'user' => (new UserResource($user))->resolve(),
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('role');

        return Inertia::render('Admin/Users/Edit', [
            'user' => (new UserResource($user))->resolve(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $deactivating = $request->has('is_active') && ! $request->boolean('is_active');
        $changingRole = isset($validated['role_id']) && (int) $validated['role_id'] !== $user->role_id;
        $removingAdministratorAccess = $user->isAdministrator() && ($deactivating || $changingRole);

        if ($removingAdministratorAccess && User::query()->whereHas(
            'role',
            fn ($query) => $query->where('name', 'Administrator'),
        )->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'is_active' => 'No se puede desactivar o quitar el rol al último administrador activo.',
            ]);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
