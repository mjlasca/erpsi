<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Requests\WarehouseRequest;
use App\Modules\Inventory\Resources\WarehouseResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Warehouse::class);

        $warehouses = Warehouse::query()
            ->withCount('stockItems')
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('inventory/warehouses/index', [
            'warehouses' => WarehouseResource::collection($warehouses)->resolve(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Warehouse::class);

        return Inertia::render('inventory/warehouses/create');
    }

    public function store(WarehouseRequest $request): RedirectResponse
    {
        $this->authorize('create', Warehouse::class);

        $warehouse = Warehouse::query()->create($request->validated());

        return redirect()
            ->route('inventory.warehouses.show', $warehouse)
            ->with('success', 'Almacén creado correctamente.');
    }

    public function show(Warehouse $warehouse): Response
    {
        $this->authorize('view', $warehouse);

        $warehouse->loadCount(['stockItems', 'movements']);

        return Inertia::render('inventory/warehouses/show', [
            'warehouse' => (new WarehouseResource($warehouse))->resolve(),
        ]);
    }

    public function edit(Warehouse $warehouse): Response
    {
        $this->authorize('update', $warehouse);

        return Inertia::render('inventory/warehouses/edit', [
            'warehouse' => (new WarehouseResource($warehouse))->resolve(),
        ]);
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return redirect()
            ->route('inventory.warehouses.show', $warehouse)
            ->with('success', 'Almacén actualizado correctamente.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('delete', $warehouse);

        try {
            $warehouse->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar el almacén porque tiene registros relacionados.');
        }

        return redirect()
            ->route('inventory.warehouses.index')
            ->with('success', 'Almacén eliminado correctamente.');
    }
}
