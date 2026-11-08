<?php

namespace App\Modules\Purchasing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Requests\SupplierRequest;
use App\Modules\Purchasing\Resources\SupplierResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('purchasing/suppliers/index', [
            'suppliers' => SupplierResource::collection($suppliers)->resolve(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Supplier::class);

        return Inertia::render('purchasing/suppliers/create');
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::query()->create($request->validated());

        return redirect()
            ->route('purchasing.suppliers.show', $supplier)
            ->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Supplier $supplier): Response
    {
        $this->authorize('view', $supplier);

        $supplier->loadCount(['purchases', 'payments']);

        return Inertia::render('purchasing/suppliers/show', [
            'supplier' => (new SupplierResource($supplier))->resolve(),
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        $this->authorize('update', $supplier);

        return Inertia::render('purchasing/suppliers/edit', [
            'supplier' => (new SupplierResource($supplier))->resolve(),
        ]);
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return redirect()
            ->route('purchasing.suppliers.show', $supplier)
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        try {
            $supplier->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar el proveedor porque tiene registros relacionados.');
        }

        return redirect()
            ->route('purchasing.suppliers.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }
}
