<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Enums\UnitType;
use App\Modules\Catalog\Models\Unit;
use App\Modules\Catalog\Requests\UnitRequest;
use App\Modules\Catalog\Resources\UnitResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Unit::class);

        $units = Unit::query()
            ->withCount('products')
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('abbreviation', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('catalog/units/index', [
            'units' => UnitResource::collection($units)->resolve(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Unit::class);

        return Inertia::render('catalog/units/create', [
            'unitTypes' => UnitType::options(),
        ]);
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        $this->authorize('create', Unit::class);

        $unit = Unit::query()->create($request->validated());

        return redirect()
            ->route('catalog.units.edit', $unit)
            ->with('success', 'Unidad creada correctamente.');
    }

    public function show(Unit $unit): Response
    {
        $this->authorize('view', $unit);

        $unit->loadCount('products');

        return Inertia::render('catalog/units/show', [
            'unit' => (new UnitResource($unit))->resolve(),
        ]);
    }

    public function edit(Unit $unit): Response
    {
        $this->authorize('update', $unit);

        return Inertia::render('catalog/units/edit', [
            'unit' => (new UnitResource($unit))->resolve(),
            'unitTypes' => UnitType::options(),
        ]);
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->authorize('update', $unit);

        $unit->update($request->validated());

        return redirect()
            ->route('catalog.units.edit', $unit)
            ->with('success', 'Unidad actualizada correctamente.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);

        try {
            $unit->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar la unidad porque tiene productos asociados.');
        }

        return redirect()
            ->route('catalog.units.index')
            ->with('success', 'Unidad eliminada correctamente.');
    }
}
