<?php

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\TaxRate;
use App\Modules\Administration\Requests\StoreTaxRateRequest;
use App\Modules\Administration\Requests\UpdateTaxRateRequest;
use App\Modules\Administration\Resources\TaxRateResource;
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

        return Inertia::render('Admin/TaxRates/Index', [
            'taxRates' => $taxRates->through(fn ($taxRate): array => (new TaxRateResource($taxRate))->resolve()),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TaxRate::class);

        return Inertia::render('Admin/TaxRates/Create');
    }

    public function store(StoreTaxRateRequest $request): RedirectResponse
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

        return Inertia::render('Admin/TaxRates/Show', [
            'taxRate' => (new TaxRateResource($taxRate))->resolve(),
        ]);
    }

    public function edit(TaxRate $taxRate): Response
    {
        $this->authorize('update', $taxRate);

        return Inertia::render('Admin/TaxRates/Edit', [
            'taxRate' => (new TaxRateResource($taxRate))->resolve(),
        ]);
    }

    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate): RedirectResponse
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

        if ($taxRate->purchaseDetails()->exists() || $taxRate->saleDetails()->exists()) {
            return back()->with('error', 'No se puede eliminar el impuesto porque tiene documentos asociados.');
        }

        $taxRate->delete();

        return redirect()
            ->route('admin.tax-rates.index')
            ->with('success', 'Impuesto eliminado correctamente.');
    }
}
