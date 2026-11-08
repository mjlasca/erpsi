<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Requests\StoreWarehouseRequest;
use App\Modules\Inventory\Requests\UpdateWarehouseRequest;
use App\Modules\Inventory\Resources\WarehouseResource;
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

        return Inertia::render('Inventory/Warehouses/Index', [
            'warehouses' => $warehouses->through(fn ($warehouse): array => (new WarehouseResource($warehouse))->resolve()),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Warehouse::class);

        return Inertia::render('Inventory/Warehouses/Create');
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
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

        return Inertia::render('Inventory/Warehouses/Show', [
            'warehouse' => (new WarehouseResource($warehouse))->resolve(),
        ]);
    }

    public function edit(Warehouse $warehouse): Response
    {
        $this->authorize('update', $warehouse);

        return Inertia::render('Inventory/Warehouses/Edit', [
            'warehouse' => (new WarehouseResource($warehouse))->resolve(),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
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

        if ($warehouse->stockItems()->exists()
            || $warehouse->lots()->exists()
            || $warehouse->movements()->exists()
            || $warehouse->purchases()->exists()
            || $warehouse->sales()->exists()) {
            return back()->with('error', 'No se puede eliminar el almacén porque tiene registros relacionados.');
        }

        $warehouse->delete();

        return redirect()
            ->route('inventory.warehouses.index')
            ->with('success', 'Almacén eliminado correctamente.');
    }
}
