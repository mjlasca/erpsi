<?php

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\TaxRate;
use App\Modules\Sales\Actions\RecalculateSaleTotals;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;
use App\Modules\Sales\Requests\SaleDetailRequest;
use App\Modules\Sales\Requests\StoreSaleDetailRequest;
use App\Modules\Sales\Requests\UpdateSaleDetailRequest;
use App\Modules\Sales\Resources\SaleDetailResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleDetailController extends Controller
{
    public function __construct(protected RecalculateSaleTotals $recalculateTotals) {}

    public function index(Sale $sale): AnonymousResourceCollection
    {
        $this->authorize('view', $sale);

        return SaleDetailResource::collection(
            $sale->details()->with(['product', 'variant', 'taxRate', 'lotAllocations.lot'])->get(),
        );
    }

    public function store(StoreSaleDetailRequest $request, Sale $sale): RedirectResponse
    {
        $this->authorize('update', $sale);

        $sale->details()->create($this->attributes($request));

        $this->recalculateTotals->execute($sale);

        return redirect()
            ->route('sales.sales.show', $sale)
            ->with('success', 'Línea de venta agregada correctamente.');
    }

    public function show(Sale $sale, SaleDetail $detail): SaleDetailResource
    {
        $this->authorize('view', $sale);

        abort_unless($detail->sale_id === $sale->id, 404);

        return new SaleDetailResource($detail->load(['product', 'variant', 'taxRate', 'lotAllocations.lot']));
    }

    public function update(
        UpdateSaleDetailRequest $request,
        Sale $sale,
        SaleDetail $detail,
    ): RedirectResponse {
        $this->authorize('update', $detail);

        abort_unless($detail->sale_id === $sale->id, 404);

        $detail->update($this->attributes($request));

        $this->recalculateTotals->execute($sale);

        return redirect()
            ->route('sales.sales.show', $sale)
            ->with('success', 'Línea de venta actualizada correctamente.');
    }

    public function destroy(Sale $sale, SaleDetail $detail): RedirectResponse
    {
        $this->authorize('delete', $detail);

        abort_unless($detail->sale_id === $sale->id, 404);

        $detail->delete();

        $this->recalculateTotals->execute($sale);

        return redirect()
            ->route('sales.sales.show', $sale)
            ->with('success', 'Línea de venta eliminada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(SaleDetailRequest $request): array
    {
        $data = $request->validated();
        $taxRate = isset($data['tax_rate_id'])
            ? TaxRate::query()->whereKey((int) $data['tax_rate_id'])->value('rate')
            : null;

        return [
            ...$data,
            'discount' => $data['discount'] ?? 0,
            'tax_rate' => $taxRate ?? 0,
        ];
    }
}
