<?php

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\TaxRate;
use App\Modules\Administration\Requests\TaxRateRequest;
use App\Modules\Administration\Resources\TaxRateResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxRateController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TaxRate::class);

        $taxRates = TaxRate::query()
            ->withCount(['purchaseDetails', 'saleDetails'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('jurisdiction', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/tax-rates/index', [
            'taxRates' => TaxRateResource::collection($taxRates)->resolve(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TaxRate::class);

        return Inertia::render('admin/tax-rates/create');
    }

    public function store(TaxRateRequest $request): RedirectResponse
    {
        $this->authorize('create', TaxRate::class);

        $taxRate = TaxRate::query()->create($request->validated());

        return redirect()
            ->route('admin.tax-rates.show', $taxRate)
            ->with('success', 'Impuesto creado correctamente.');
    }

    public function show(TaxRate $taxRate): Response
    {
        $this->authorize('view', $taxRate);

        $taxRate->loadCount(['purchaseDetails', 'saleDetails']);

        return Inertia::render('admin/tax-rates/show', [
            'taxRate' => (new TaxRateResource($taxRate))->resolve(),
        ]);
    }

    public function edit(TaxRate $taxRate): Response
    {
        $this->authorize('update', $taxRate);

        return Inertia::render('admin/tax-rates/edit', [
            'taxRate' => (new TaxRateResource($taxRate))->resolve(),
        ]);
    }

    public function update(TaxRateRequest $request, TaxRate $taxRate): RedirectResponse
    {
        $this->authorize('update', $taxRate);

        $taxRate->update($request->validated());

        return redirect()
            ->route('admin.tax-rates.show', $taxRate)
            ->with('success', 'Impuesto actualizado correctamente.');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $this->authorize('delete', $taxRate);

        try {
            $taxRate->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar el impuesto porque tiene documentos asociados.');
        }

        return redirect()
            ->route('admin.tax-rates.index')
            ->with('success', 'Impuesto eliminado correctamente.');
    }
}
