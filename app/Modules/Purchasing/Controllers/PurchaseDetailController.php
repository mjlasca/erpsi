<?php

namespace App\Modules\Purchasing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\TaxRate;
use App\Modules\Purchasing\Actions\RecalculatePurchaseTotals;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseDetail;
use App\Modules\Purchasing\Requests\PurchaseDetailRequest;
use App\Modules\Purchasing\Requests\StorePurchaseDetailRequest;
use App\Modules\Purchasing\Requests\UpdatePurchaseDetailRequest;
use App\Modules\Purchasing\Resources\PurchaseDetailResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseDetailController extends Controller
{
    public function __construct(protected RecalculatePurchaseTotals $recalculateTotals) {}

    public function index(Purchase $purchase): AnonymousResourceCollection
    {
        $this->authorize('view', $purchase);

        return PurchaseDetailResource::collection(
            $purchase->details()->with(['product', 'variant', 'taxRate'])->get(),
        );
    }

    public function store(StorePurchaseDetailRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('update', $purchase);

        $purchase->details()->create($this->attributes($request));

        $this->recalculateTotals->execute($purchase);

        return redirect()
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Línea de compra agregada correctamente.');
    }

    public function show(Purchase $purchase, PurchaseDetail $detail): PurchaseDetailResource
    {
        $this->authorize('view', $purchase);

        abort_unless($detail->purchase_id === $purchase->id, 404);

        return new PurchaseDetailResource($detail->load(['product', 'variant', 'taxRate']));
    }

    public function update(
        UpdatePurchaseDetailRequest $request,
        Purchase $purchase,
        PurchaseDetail $detail,
    ): RedirectResponse {
        $this->authorize('update', $detail);

        abort_unless($detail->purchase_id === $purchase->id, 404);

        $detail->update($this->attributes($request));

        $this->recalculateTotals->execute($purchase);

        return redirect()
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Línea de compra actualizada correctamente.');
    }

    public function destroy(Purchase $purchase, PurchaseDetail $detail): RedirectResponse
    {
        $this->authorize('delete', $detail);

        abort_unless($detail->purchase_id === $purchase->id, 404);

        $detail->delete();

        $this->recalculateTotals->execute($purchase);

        return redirect()
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Línea de compra eliminada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(PurchaseDetailRequest $request): array
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
